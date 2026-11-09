# r03 — local: reservations, sales, inquiries, journal and notifications on an offer

Question: How do reservations, sales and inquiries work on an offer (who creates them, how they change quantity and offer status, can they be undone), and what journal entries and notifications does each offer event produce for whom? · V1 commit: 511ce3b

## Summary
- Stock is derived, never stored: available = offer.quantity − SUM(sales.quantity) − SUM(reservations.quantity). Reservations and sales are rows; there is no stored "available" column.
- Reservation: created by an admin (StockSaleTab, any label), by the offer owner, non-admin (UserReservationSection, fixed label), by accepting an inquiry (reserves ALL available stock), or imported from WooCommerce. Ends by deletion (unreserve, status back to Aktywna) or by "sell reserved" (row deleted, sale row created).
- Sale: created only by "Sprzedaj" (admin), "sell reserved" (admin/owner) or Woo import. Undo only for admins: "Zwrot sprzedaży" zeroes the sale row's quantity and sets the offer to Aktywna.
- Inquiry: an admin sends a message to the offer's owner (the inquiry's user_id is the OWNER, not the asker). Statuses Dostarczone → Przeczytane (auto, on opening) → Zaakceptowane / Odrzucone (owner decides). Accepting reserves all remaining stock.
- Journal entries and bell notifications are always addressed to the offer owner (also when the owner is the actor); inquiry decisions go to all admins. Texts are hard-coded in the Journal action classes (confirmed).
- Several status/quantity rules are inconsistent (full-reserved test ignores sales, partial actions force Aktywna, undo actions force Aktywna). See Contradictions.
- Production: 318 sales (314 qty 1, 4 qty 0 = returned), 14 reservations, 20 inquiries, 3571 journal entries, 6888 bell notifications.

## Findings

### Available quantity formula and the "may change stock" gate
- Tag: confirmed · Impact: high
- What happens: available = quantity − total sold − total reserved (sums of the row quantities). Stock actions (Wycofaj szybę / Wyślij zapytanie / Sprzedaj / Zarezerwuj, and the owner's Zarezerwuj) are shown only if available ≥ 1 AND offer status is Aktywna or Zarezerowana. getMinimumQuantity() = sold + reserved (floor for editing quantity; use is r02's).
- Evidence: app/Filament/Traits/HasSalesRecords.php:35-75 getTotalSoldAttribute, getTotalReservedAttribute, getMinimumQuantity, getAvailableQuantityAttribute, allowedToStockChange; StockSaleTab.php:284 `->visible($record->allowedToStockChange())`; UserReservationSection.php:110 same.
- Note: a sale row with quantity 0 (returned) counts for nothing, so a return releases stock automatically.

### Admin reserves quantity on an offer ("Zarezerwuj")
- Tag: confirmed · Impact: high
- What happens: admin (Administracja tabs are admin-only) opens the "Zarzadzanie" tab → "Zarezerwuj", confirmation "Czy na pewno chcesz zarezerować tę ofertę?", form: "Podaj ilośc:" (integer, min 1, max = available, default 1) and required "Oznaczenie rezerwacji" (placeholder "Nazwa do późniejszej identyfikacji"). Action refuses amount ≤0 or > available with error "Operacja nie powiodła się" / "Wprowadzona ilośc jest niezgodna z obecnym stanem". Creates an offer_reservations row (user_id = acting user, quantity, label). Then: if quantity − total reserved === 0 → status Zarezerowana + journal "Twoja oferta została zarezerwowana!"; else status forced to Aktywna, sold_date = null + journal "Rezerwacja Twojej oferty!" with " Zarezerwowana ilość: N" appended.
- Evidence: StockSaleTab.php:252-278; OfferReservationChangeAction.php:18-45; OfferFullyReservedAction.php:31-36; OfferAmountReservedAction.php:31-37.

### Offer owner (non-admin) reserves their own offer
- Tag: confirmed · Impact: high
- What happens: section visible only to the offer's owner when the owner is not an admin; same "Zarezerwuj" form without a label field; label fixed to "Rezerwacja własciciela oferty". Same action, same status and journal effects (the owner gets a journal entry about reserving their own offer). Owner also sees "Moje rezerwacje" (only reservations whose user_id = offer owner) with per-row "Oznacz jako sprzedane" and "Usuń rezerwacje".
- Evidence: UserReservationSection.php:28 visible(), :33-71, :106; HasSalesRecords.php:23-26 userReservations.

### Reserving via accepted inquiry reserves everything that is left
- Tag: confirmed · Impact: high
- What happens: AcceptInquiryAction reserves the full available quantity ("for now reserve full stock" comment) with label "Rezerwacja z zapytania: <inquiry title>"; attributed to the acting user (the owner). If available is 0 nothing is reserved but inquiry is still marked accepted.
- Evidence: AcceptInquiryAction.php:34-39.

### Sale: admin "Sprzedaj"
- Tag: confirmed · Impact: high
- What happens: "Sprzedaj" with confirm "Czy na pewno chcesz sprzedać" and note "Uzytkownik zostanie poinformowany, że jego oferta została sprzedana. ", form "Podaj ilośc:" (1..available). Validates amount like reservation. Creates offer_sales row (quantity, sold_at = now; label and sold_by are never set by this path). If quantity − total sold === 0: status Sprzedana, sold_date = now, fires OfferSoldEvent (→ deactivates Otomoto adverts), journal "Twoja oferta została sprzedana!" (content "Nowy status oferty: Sprzedana"). Else: status forced Aktywna, sold_date null, journal "Sprzedaż Twojej oferty!" + " Sprzedana ilość: N".
- Evidence: StockSaleTab.php:231-250; OfferStockChangeAction.php:21-45; OfferFullySoldAction.php:33-40; OfferAmountSoldAction.php:31-37; OfferEventServiceProvider.php:26-28; DeactivateAdvertListener.php:25-37.

### Sale: "sell reserved" (admin on own reservation, owner on own reservation)
- Tag: confirmed · Impact: high
- What happens: deletes the reservation row (hard delete, no soft deletes on the model) and then creates a sale of that quantity through the same stock action, with the same status/journal effect. StockSaleTab shows the buttons only on reservations whose user is the logged-in user ("Sprzedaj zarezerowane", confirmation; success toast "Oferta została sprzedana" shown BEFORE the sale runs). Owner variant label "Oznacz jako sprzedane". Reservations made by someone else are listed but have no actions for the viewer, so an admin cannot release another person's reservation from the UI.
- Evidence: StockSaleTab.php:98-128; UserReservationSection.php:45-53.

### Undo of a reservation
- Tag: confirmed · Impact: high
- What happens: "Usuń rezerwacje" (confirm) hard-deletes the row, sets offer status to Aktywna unconditionally (even if other reservations remain or the offer was Sprzedana/Archiwalna), toast "Rezerwacja została usunięta" (admin tab). No journal entry, no notification, no event. Only the reservation's own author can do it.
- Evidence: StockSaleTab.php:111-122; UserReservationSection.php:55-64.

### Undo of a sale (admin only)
- Tag: confirmed · Impact: high
- What happens: "Zwrot sprzedaży" (confirm, required "Oznaczenie zwrotu", default "Zwrot z <now>") visible per sale row with quantity > 0, to admins only. It sets the sale row's quantity to 0 and label to the entered text (row kept as history), then sets offer status Aktywna. sold_date is not cleared; no journal, no notification, no Otomoto/Woo-specific call from here. Partial return is not possible (whole row). Prod: 4 sales have quantity 0 and 4 carry "Zwrot…" labels.
- Evidence: StockSaleTab.php:150-175; prod query (sales qty 0 = 4).

### Archive ("Wycofaj szybę") is a stock-tab action
- Tag: confirmed · Impact: high
- What happens: confirm "Czy na pewno chcesz wycofać tę szybę?" / "Spowoduje to zmianę statusu w Wordpress oraz Otomoto". Sets status Archiwalna, fires OfferArchivedEvent (→ Otomoto advert deactivation), journal "Oferta została wycofana" / "Twoja oferta została wycofana ze sprzedaży." to owner. Visible under the same gate (available ≥1, Aktywna/Zarezerowana). Existing reservations are left as rows. The Woo claim in the modal text: only the event→Otomoto listener was found here (see Could not establish).
- Evidence: StockSaleTab.php:191-200; OfferArchivedAction.php:27-34; OfferEventServiceProvider.php:29-31.

### Reserved-offers lists
- Tag: confirmed · Impact: normal
- What happens: admin page "Zarezerwowane oferty (admin)" (nav "Zarezerwowane", admin only) lists all offers with status Zarezerowana; nav badge = their count. Non-admin page "Zarezerwowane oferty" lists only the user's own Zarezerowana offers, badge likewise. Columns include "Ostatnia rezerwacja", "Oznaczenie" (latest reservation's date/label). Row actions: view, replicate, edit/delete when canBeEdited(); bulk: delete, create advert, Woo sync, "Usuń na zawsze" (force delete + deletes product in Woo). Filters: stan używania, typ szyby, producent, kolor, rating, "Synchronizacja z WP". Note: only offers in status Zarezerowana appear, so a partial reservation (status Aktywna) never shows here.
- Evidence: AdminReservedOffersPage.php:36-52; UserReservedOffersPage.php:48-124; AdminReservedOffersTable.php; ReservedOfferFilters.php.

### Inquiry creation (admin → owner)
- Tag: confirmed · Impact: high
- What happens: admin, on the stock tab, "Wyślij zapytanie" (heading "Wyślij zapytanie ofertowe do użytkownika"; fields "Tytuł" required, "Treśc/Informacje" rich text, not required). Creates offer_inquiries row with status Dostarczone, user_id = the offer's owner, offer_id. If offer has no owner: error "Uzytkownik oferty nie istnieje". Toast "Pomyślnie wysłano zapytanie" / "Użytkownik oferty otrzymał wiadomość". Owner gets a bell notification "Nowe zapytanie ofertowe!" / "Otrzymałeś nowe zapytanie do jednej ze swojej oferty" with a "zobacz" button that has NO url. The admin resource's create/edit pages are commented out; there is no way to create an inquiry outside this action. The sender (admin) is not recorded on the row. Whether the inquiry represents an external customer's question is not stored (title/content free text).
- Evidence: StockSaleTab.php:202-228; CreateInquiryAction.php:16-52; OfferInquiryResource.php:94-101; migration 2025_11_15_153812.

### Inquiry lifecycle and owner decisions
- Tag: confirmed · Impact: high
- What happens: owner (non-admin) page "Zapytania ofertowe" lists inquiries where user_id = self, newest first, filter "Status zapytania", badge = count of Dostarczone. Opening "Zobacz" modal flips Dostarczone → Przeczytane (mountUsing, silent). Only while Przeczytane, modal footer offers "Zarezerwuj szybę" (confirm "Spowoduje to rezerwację szyby w panelu") and "Rezygnacja" ("Potwierdź rezygnację zapytania"). Accept: inquiry Zaakceptowane, toast "Pomyślnie zatwierdzono zgłoszenie", reserve all available stock (see above), notify all admins "Uzytkownik zatwierdził zapytanie ofertowe" (body names the user and title, "Oferta została zarezerowana", button "Zobacz ofertę"). Missing offer: error "Oferta nie istnieje" and no change. Reject: Odrzucone, toast "Pomyślnie odrzucono zgłoszenie", admins notified "Uzytkownik odrzucił zapytanie ofertowe" (danger). Decisions are final in the UI (buttons only for Przeczytane; no reopening, no expiry found). Accept/Reject do not check that the offer is still reservable (Accept just skips the reservation if available is 0, but the admin toast still says it was reserved).
- Evidence: UserInquiriesPage.php:39-60; UserInquiriesTable.php (getRowActions, mountUsing); AcceptInquiryAction.php:19-64; RejectInquiryAction.php:19-52; InquiryStatusEnum.php (labels Dostarczone/Przeczytane/Zaakceptowane/Odrzucone).

### Admin inquiry list
- Tag: confirmed · Impact: normal
- What happens: admin resource "Zapytania ofertowe" (admin only) lists all inquiries (soft-deleted visible via TrashedFilter), columns offer photo, offer SKU ("Usunięta" if the offer is gone), title, user, status; filters status, "Użytkownicy", trashed; row "Szczegóły" modal (read-only, does not change status), "usuń" = ForceDelete with confirm "Spowoduje trwałe usunięcie zapytania"; bulk delete and force delete. Navigation badge counts REJECTED inquiries (danger colour), not new ones. Inquiries cascade-delete with the offer (FK cascadeOnDelete); user deleted → user_id null. Prod has 8 soft-deleted rows although the UI exposes only force delete and bulk soft delete.
- Evidence: OfferInquiryResource.php:30-48; AdminInquiriesTable.php; migration 2025_11_15 (cascadeOnDelete, nullOnDelete) and 2025_11_17 (softDeletes); Models/OfferInquiry.php.

### Journal mechanism (who gets what)
- Tag: confirmed · Impact: normal
- What happens: every JournalAction runs its processAction (the status change) first, then looks up the offer's owner (offer.user_id); if the owner exists, writes a user_journals row (offer_id, type, title, content + extra info) and sends a Filament database ("bell") notification with the same title, info style, icon heroicon-o-newspaper, colour by type, and a "zobacz" button to "Mój dziennik" filtered by offer. If the owner is missing, the status still changes but nothing is recorded. The recipient is always the owner, including when the owner is also the actor (admin's own offers, owner's own reservation). Notification is sent synchronously (notifyNow). Types: Info (primary), Success (success), Warning (warning, never used), Rejection (danger).
- Evidence: JournalAction.php:21-54; User.php:156-172 createJournalEntry; JournalType.php.

### Journal and notification map (trigger → status → recipient)
- Tag: confirmed · Impact: normal
- Text hard-coded in the action classes (confirms the Settings note; none read from settings). Prod counts in brackets are journal rows with that title.

| Journal action / title | Type | Trigger | Offer status set | Recipients |
|---|---|---|---|---|
| OfferCreatedAction "Dodałeś nową ofertę" [1388] | Success | OfferCreatedByUserAction for a non-draft offer (create screen, r02) | none | owner journal+bell; also sets a 1-minute cache flag (non-admin owner) that shows an on-page notice on ViewOffer |
| DraftOfferCreatedAction "Dodałeś nowy szkic" [0] | Success | same, when status Szkic | none | owner journal+bell; same cache flag for draft notice |
| ActivateFromDraftAction "Aktywowałeś ofertę!" [0] | Success | MyOffers row action "Aktywuj" (draft only) | Aktywna if owner is admin, else Oczekująca; non-admin also creates a Submission "Użytkownik utworzył ofertę (ze szkicu)" | owner journal+bell; all admins bell "Uzytkownik dodał nową ofertę! (ze szkicu)" with buttons to offer and submission |
| OfferAcceptedAction "Oferta została zaakceptowana!" [1721] | Success | admin "Zatwierdź ofertę" (offer in Oczekująca or Odrzucona), bulk AcceptOffersBulkAction, submission resolution with new_status Aktywna (reason appended) | Aktywna | owner |
| OfferRejectedAction "Oferta została odrzucona" [24] | Rejection | admin "Odrzuć ofertę" (offer Oczekująca or Aktywna, reason required "Powód odrzucenia"), submission resolution Odrzucona | Odrzucona | owner (content ends with the reason) |
| OfferFullyReservedAction "Twoja oferta została zarezerwowana!" [215 incl. partial] | Info | reservation leaving quantity − reserved = 0 | Zarezerowana | owner |
| OfferAmountReservedAction "Rezerwacja Twojej oferty!" | Info | any other reservation | Aktywna, sold_date null | owner |
| OfferFullySoldAction "Twoja oferta została sprzedana!" [201] | Success | sale leaving quantity − sold = 0 | Sprzedana, sold_date now, OfferSoldEvent | owner |
| OfferAmountSoldAction "Sprzedaż Twojej oferty!" | Success | other sale | Aktywna, sold_date null | owner |
| OfferArchivedAction "Oferta została wycofana" [22] | Info | "Wycofaj szybę" | Archiwalna, OfferArchivedEvent | owner |

Non-journal bell notifications: owner "Nowe zapytanie ofertowe!" (inquiry created); admins on inquiry accepted/rejected (above); admins on draft activation (above); admins on non-draft offer creation by a non-admin (OfferCreatedByUserAction:51-55, r02/r04); admins on profile-related activity via UserNotificationService (out of scope). Counts above for partial reservation/sale titles: prod has no rows titled "Rezerwacja Twojej oferty!" or "Sprzedaż Twojej oferty!" (the 215 and 201 are the full-state titles), so partial quantities have effectively never happened (consistent with sales quantity = 1).
- Evidence: Actions/Journal/*.php (all); AdminTab.php:100-170; AcceptOffersBulkAction.php:89; ViewSubmission.php:470-487; MyOffers.php:204-214; ActivateFromDraftAction.php:26-77; OfferCreatedByUserAction.php:44-55; prod GROUP BY user_journals.

### Reading the journal ("Mój dziennik")
- Tag: confirmed · Impact: normal
- What happens: every user (admins too) sees only own entries (query filtered by user_id), newest first; nav badge = own unread count. Eye icon column marks read ("Wiadomośc odczytana" / "Oznacz jako przeczytaną"); opening "Szczegóły" marks read and offers "Przejdź do oferty" (blocked with warning "Oferta została usunięta lub nie istnieje" / "Brak możliwości zobaczenia oferty" if the offer is missing or status Usunięta). "Usuń" removes one's own entry (hard delete; abort 403 otherwise); bulk delete too. Entries are never created by hand (create/edit pages commented out). Entries cascade-delete with the user. Journal rows have no FK to the offer (offer_id plain nullable), so they survive deletion of the offer and show "Oferta nie istnieje". The policy file is entirely commented out, so it enforces nothing. Prod: very low read rates (about 22 read of 3571; 3571 mostly unread).
- Evidence: UserJournalResource.php:60-180; UserJournalPolicy.php (all lines commented); migration 2024_12_01_180306; prod GROUP BY.

### Bell notifications table
- Tag: confirmed · Impact: normal
- What happens: Filament database notifications (polymorphic notifiable, JSON data, read_at) shown in the panel bell for each user. Prod: 6888 rows, 4932 unread. user_notifications is a separate, unrelated table ("Aktywność użytkowników" admin list, produced by UserNotificationService; 8 rows).
- Evidence: AdminPanelProvider.php:99; migration 2024_10_24; UserNotificationService.php; ListUsersNotifications.php.

### WooCommerce import creates sales and reservations
- Tag: confirmed · Impact: high
- What happens: import of Woo products creates an Offer owned by setting `woo-offers-default-user` (fallback user 1); if Woo status is "Sprzedana", creates a sale of the full quantity labelled "Status sprzedaży z wordpress"; else if the product has the ACF reservation flag, a reservation of the full quantity labelled "Status rezerwacji z wordpress" by that default user. No journal, no notification. (Prod: 35 sales carry the Woo label; 0 reservations carry the Woo label.)
- Evidence: FetchWooProducts.php:138-153; prod query.

## Contradictions and defects
- defect? Full-reservation test ignores sales: `$record->quantity - getTotalReservedAttribute() === 0` (OfferReservationChangeAction.php:36-38). Example qty 3, 1 sold, reserve 2 → available 0 but status Aktywna and journal "Rezerwacja Twojej oferty!". Mirror: full-sale test ignores reservations (OfferStockChangeAction.php:39), so selling the last unreserved piece while another is reserved sets Aktywna not Zarezerowana, and selling all qty when others reserved is impossible via the check but status logic is not state-based.
- defect? Partial-reserve/partial-sale actions force status Aktywna even if the offer was Zarezerowana (a second sale of one piece out of a fully reserved offer is impossible due to the available check, but a partial sale of 1 of 2 where 1 reserved ends Aktywna while available is 0).
- defect? Unreserve and sale return force Aktywna regardless of previous status or remaining reservations/sales; they also send no journal (OfferResource StockSaleTab.php:117,167).
- defect? Sale return does not clear sold_date (StockSaleTab.php:163-169); partial actions do clear it.
- defect? "sell reserved" first deletes the reservation, then success toast, then sale; sale errors (none expected) would leave stock released (StockSaleTab.php:104-109).
- defect? Admin pending-offer "Odrzuć ofertę" ends with `->after(fn($livewire) => dd($livewire))` (AdminTab.php:122) — debug dump after rejection.
- defect? Inquiry "zobacz" bell button has no URL (CreateInquiryAction.php:44-47).
- defect? Admin inquiry nav badge counts Odrzucone, not new/needing attention (OfferInquiryResource.php:35-40).
- defect? AcceptInquiryAction toast/notification says the offer was reserved even if available is 0 (AcceptInquiryAction.php:34-60).
- defect? Owner "Moje rezerwacje" only lists reservations authored by the owner (userReservations uses offer.user_id), so reservations made by admins are invisible to owners, though they reduce stock.
- defect? JournalAction notifications and OfferNotificationHandler: PanelNotification::getRecipients returns [] and OfferNotificationHandler::notifyOfferCreatedByUser sends a placeholder "Saved successfully" notification; registered as singleton but never called anywhere (grep) — dead code.
- Texts have typos (kept verbatim): "Częśc", "zarezerowana", "Uzytkownik", "ilośc".
- offer_reservations has `sold_by`, offer_sales has `label`/`sold_by`, never written by actions (sold_by always NULL in prod: 318/318).
- No unique/FK constraints for offer_id/user_id on reservations/sales (foreignIdFor without constrained), so deleting an offer leaves orphan sales/reservations (inferred: Offer soft-deletes make this moot; hard delete path "Usuń na zawsze" not examined).

## Leads outside scope
- Offer status changes and sold_date/archived_date handling in the Offer model, OfferUpdatedEvent/Woo sync on status change (r01).
- Non-admin edit of quantity vs getMinimumQuantity (r02).
- OfferCreatedByUserAction submission creation and admin notification (r02/r04).
- Otomoto DeactivateAdvertAction internals (OfferSoldEvent / OfferArchivedEvent).
- Whether Woo status is updated after sale/archive/unreserve (modal text promises it for archive).
- AcceptOffersBulkAction.php conditions for bulk accept.

## Could not establish
- Whether returning a sale or unreserving re-activates Woo/Otomoto adverts: no code in these paths; would require tracing Offer model hooks (r01) or runtime.
- Whether who "asks" in an inquiry is a real customer: model stores only title/content; business answer needed on the real-world process (the 20 prod rows exist: accepted 3, read 6, rejected 6, delivered 5; 8 soft-deleted).
- Why prod has 8 soft-deleted inquiries when UI offers only force delete / bulk delete: bulk DeleteBulkAction does soft delete (inferred).

## Coverage ledger
- Searched: OfferStockChangeAction, OfferReservationChangeAction, every Journal action class name, CreateInquiryAction, createJournalEntry, OfferSoldEvent, OfferArchivedEvent, UserNotification, pending_offer_status_notice, sold_date, OfferSale::, OfferReservation::, ->reservations(, databaseNotifications; prod aggregates on offer_inquiries, offer_sales, offer_reservations, user_journals, notifications, user_notifications, offers.
- Examined: Models OfferReservation, OfferSale, OfferInquiry, UserJournal, UserNotification; Actions/Stock/*; Traits/HasSalesRecords; OfferInquiryResource + Enums, Actions (Accept/Create/Reject), Pages (List/Edit), Tables (Admin/User); Pages AdminReservedOffersPage, UserReservedOffersPage, UserInquiriesPage; Abstracts/JournalAction; Actions/Journal/* (all 10 incl. ActivateFromDraft); Enums/JournalType; UserJournalResource and pages; UserJournalPolicy; NotificationService (PanelNotification, OfferNotificationHandler); migrations for the 7 tables; one hop out: StockSaleTab, UserReservationSection, User::createJournalEntry, AdminTab 100-170, ViewSubmission 470-487, OfferCreatedByUserAction, MyOffers 204-214, OfferEventServiceProvider, DeactivateAdvertListener, FetchWooProducts 110-155, UserNotificationService, ListUsersNotifications, AdminReservedOffersTable, ReservedOfferFilters, ViewOffer 112-170.
- Matched, not examined: CreateOfferInquiry.php and CreateUserJournal/EditUserJournal pages (commented routes, unreachable); ReservedOffersTableSchema (column list only grepped); AcceptOffersBulkAction (r02/r04); OtomotoService internals; PanelNotificationProvider (singleton registration only).
