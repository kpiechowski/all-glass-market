# Offers — discovery

<!-- Working document for the module-discovery skill. Only the manager writes it.
     Item ids are stable and never reused. Every finding cites a report (rNN) and its V1 evidence.
     Tags: confirmed · observed · inferred · ambiguous · unknown · defect? · decide -->

## A. Metadata

- **Status:** ready-for-grilling
- **Next actions:** ready-for-grilling (2026-11-09). Not grilled alone: next is the domain grilling on Vehicles + Offers (prompt `.scratch/vehicles/domain-grilling-prompt.md`), then module grilling per `grilling.md`.
- **Started:** 2026-11-09 · **Last updated:** 2026-11-09
- **V1:** `/home/kp/laravel/projekty/AllGlass/` @ `511ce3b` (dirty: `docker-compose.yml` modified, `SECURITY_INCIDENT_2026-10.md` untracked; neither touches offer behaviour)
- **V2 state:** module absent.
- **Prior material:** `docs/archive/v1-port/modules/offers/scan.md` (scope list only), `docs/archive/v1-port/modules/submissions/scan.md` (unverified, "inferred"/"likely" claims), `docs/archive/v1-port/refactor-plan.md` boundary table. Neighbour: `.scratch/vehicles/discovery/discovery.md` (ready-for-grilling) — link to it, never re-specify.
- **Data:** V1 production dump loaded locally (MySQL container `laradock8_5-mysql-1`, database `allglass_v1_prod`, last offer 2026-10-01). Read-only aggregate queries only; no personal data in reports.

### Scope

- **Apparent responsibility:** the Offer as V1 has it: a listing that is also the physical glass. Its lifecycle and statuses, approval, price and VAT, quantity, reservations, sales, inquiries, photos, versions, the per-user journal and notifications about it, and how offers come into existence (admin form, seller form, Submissions, imports).
- **Anchors:**
  - Model and domain: `app/Models/Offer.php` (658 lines), `OfferReservation`, `OfferSale`, `OfferInquiry`, `Submission`, `UserJournal`, `UserNotification`; `app/Filament/Enums/{OfferStatus,OfferUsageStatus,JournalType,ResourceStatus}.php`; `app/Services/Offer/**` (update, price, eurocode relation, status aggregate, events, provider); `app/Jobs/Offer/**`; `app/Traits`/`app/Filament/Traits/HasSalesRecords.php`.
  - Screens: `app/Filament/Clusters/Offers/Resources/OfferResource{.php,/**}`, `app/Filament/Clusters/Offers/Pages/OfferRevisionsPage.php`, `app/Filament/Pages/{FullListOffer,MyOffers,AdminReservedOffersPage,UserReservedOffersPage,UserInquiriesPage}.php`, `app/Livewire/FullListOfferTable.php`, `app/Filament/Resources/OfferResource/Widgets/**`, `app/Filament/Resources/{OfferInquiryResource,SubmissionResource,UserJournalResource}{.php,/**}`, offer relation managers (`Shared`, `UserResource`, `CarResource`).
  - Actions: `app/Filament/Actions/{OfferCreatedByUserAction,OfferEditedByUserAction}.php`, `Actions/Stock/**`, `Actions/Journal/**`, `Actions/Abstracts/JournalAction.php`.
  - Policies: `app/Policies/{OfferPolicy,SubmissionPolicy,UserJournalPolicy}.php`.
  - Notifications: `app/Services/NotificationService/**`.
  - Migrations: offers, soft deletes, tmp session, media, reservations, sales, inquiries (+ soft deletes), submissions (+ read status), user_journals, notifications, user_notifications.
- **Vocabulary:** `Offer`, `offers`, `offer_id`, `OfferStatus` (Szkic, Oczekuje zatwierdzenia, Aktywna, Odrzucona, Wygasła, Zarezerowana, Archiwalna, Sprzedana, Usunięta), `OfferUsageStatus`/`usage_status`, `quantity`, `price`, `rating`, `sku`, `tmp_session`, `OfferReservation`/`offer_reservations`, `OfferSale`/`offer_sales`, `OfferInquiry`/`offer_inquiries`/`InquiryStatusEnum`, `Submission`/`submissions`/`is_read`/`original_data`, `UserJournal`/`user_journals`/`JournalType`, `UserNotification`, `OfferUpdatedEvent`, `OfferSoldEvent`, `OfferArchivedEvent`, `OfferDeletedEvent`, `OfferStatusAggregate`, `OfferPriceService`, `OfferUpdateService`, `OfferUpdateOffersChunk`, `Versionable`/`versions`, media collections, Polish labels (Oferty, Moje oferty, Zgłoszenia, Zapytania, Rezerwacje, Dziennik).
- **Boundary assumptions:** Reservations, Sales and OfferInquiry live in Offers (archive boundary table: no existence outside an offer). Submissions is investigated here as a way an offer is created or changed (user decision 2026-11-09); whether it stays a module is decided in grilling. Glass attributes, eurocodes and cars are Vehicles: their behaviour is in `.scratch/vehicles/discovery/discovery.md` (W-01–W-03, F-01–F-21) and is only linked here.
- **Out of scope:** the attribute dictionaries, eurocodes and cars (Vehicles discovery); WooCommerce/WordPress sync internals (WooCommerce discovery; here only "what triggers a sync"); Otomoto (dropped from V2, noted only where it changes offer behaviour); Audit/ChangesLog internals (done in V2); users and roles (Users, done).
- **Limitations:** static reading of V1 plus read-only queries on a production dump; no running V1.

## B. Module overview

_Partial; completed as wave 1 returns._

- **The Offer** is one record that is both the listing and the physical glass: owner (seller), net price, usage state (Nowa / Używana / Outlet) with a 1–5 rating, SKU, eurocodes, type/colour/producer/equipment, public and private photos, quantity. 11 sellers own the 2046 offers. Nine statuses exist; production uses six (Aktywna, Sprzedana, Usunięta, Archiwalna, Zarezerowana, Odrzucona). There is no state machine: each action sets the status it wants (r01).
- **Glass parameters follow the eurocode in the data (r06):** among eurocodes with ≥ 2 live offers, the offers share the type in 99.6% of groups, the colour in 97.5%, the equipment set in 93.3%; but the producer only in 46.2%, the usage state in 66.8%, the year in 41.6%. Several offers of one eurocode are normal (255 eurocodes have 2–7 offers) and are kept as separate one-piece offers. Three of 11 sellers own 94.6% of offers.
- **Roles:** only two are used — Super Admin (3 people) and User (28 sellers); every gate in code is "is admin". A seller sees only their own offers; no screen shows other sellers' offers (r02).
- **Moderation:** every save by a seller sends the offer back to "Oczekuje zatwierdzenia"; an admin accepts or rejects it. Admins do approve (1721 "accepted" journal entries; 0 offers waiting today), mostly from the offer screen, not from tickets (r01, r03).
- **Price:** stored once; the gross price adds 23% VAT unless the owner is a company with accepted terms. The same computed price goes to the panel, WooCommerce and Otomoto (r01, r05).
- **Every offer save is an integration event:** a synchronous WooCommerce push and a queued Otomoto refresh, from model hooks; changing eurocodes counts as a save (r01, r05).
- **Submissions ("Zgłoszenia")** are not customer messages and not a public form. They are automatic **review tickets for the admin**, written when a panel user with the role User (a seller) creates a non-draft offer, edits an offer, or activates a draft. The ticket holds a snapshot of what the seller typed plus the result of eurocode matching and attribute copying. Resolving it is one of three doors to the same approval (accept → Aktywna, reject → Odrzucona); the other two are on the offer screen and in a bulk action (r04). In production the queue is effectively unused: 1182 of 1213 tickets are unread.
- **Stock** is derived, never stored: available = quantity − sold − reserved, from sale and reservation rows. Reservations come from an admin, from the owner, from an accepted inquiry or from the WooCommerce import; sales from an admin, from "sell reserved" or from the import. The offer status (Aktywna, Zarezerowana, Sprzedana) is recomputed by the stock actions, inconsistently (Q-06, Q-08). In production stock is one piece: every sale is quantity 1 (or 0 = returned), and no partial reservation or sale ever happened (r03).
- **Inquiry ("Zapytanie ofertowe")** goes from an admin to the offer's owner; the owner accepts (which reserves all remaining stock) or rejects. 20 rows in production (r03).
- **Journal ("Mój dziennik") and bell notifications** carry every offer event to the offer's owner; texts are hard-coded. Almost nobody reads them (journal 22 of 3571 read; bell 4932 of 6888 unread) (r03).

## C. Functional inventory

| ID | Functionality | Entry points | Workflow | Verified | Confidence | Open |
|---|---|---|---|---|---|---|
| F-01 | Review ticket when a seller creates an offer | CreateOffer afterCreate → `OfferCreatedByUserAction` | W-01 | r04 | high | Q-05 |
| F-02 | Review ticket when a seller edits an offer | EditOffer afterSave → `OfferEditedByUserAction` | W-02 | r04 | high | — |
| F-03 | Review ticket when a seller activates a draft | MyOffers "Aktywuj" → `ActivateFromDraftAction` | W-01 (variant) | r04 | high (never used in prod) | — |
| F-04 | Ticket list: unread badge, mark read, bulk delete | SubmissionResource, ListSubmissions | n/a: list and flag toggles, no conditions beyond read/unread | r04 | high | — |
| F-05 | Ticket view: snapshot, match result, "newer ticket exists" warning; opening marks read | ViewSubmission | n/a: display; the read flag is BR-04 | r04 | high | — |
| F-06 | Add or assign a eurocode from a ticket | ViewSubmission "Dodaj eurokod" | W-04 | r04 | high | Q-02 |
| F-07 | Resolve a ticket: accept or reject the offer, optional message, optional ticket delete | ViewSubmission "Rozwiąż zgłoszenie" | W-03 | r04, spot | high | Q-01, Q-04 |
| F-08 | Available quantity and the stock-action gate | offer view, "Zarzadzanie" tab | n/a: derived value, rules BR-11, BR-12 | r03 | high | — |
| F-09 | Admin reserves a quantity with a label | StockSaleTab "Zarezerwuj" | W-05 | r03, spot | high | Q-06 |
| F-10 | Owner reserves their own offer; "Moje rezerwacje" | UserReservationSection | W-05 | r03 | high | Q-09 |
| F-11 | Release a reservation | StockSaleTab / UserReservationSection "Usuń rezerwacje" | W-05 | r03, spot | high | Q-08 |
| F-12 | Sell a quantity, or sell a reservation | StockSaleTab "Sprzedaj", "Sprzedaj zarezerowane", owner "Oznacz jako sprzedane" | W-06 | r03 | high | Q-06 |
| F-13 | Return a sale (admin) | StockSaleTab "Zwrot sprzedaży" | W-06 | r03 | high | Q-08 |
| F-14 | Withdraw a glass from sale | StockSaleTab "Wycofaj szybę" | W-08 | r03 | high | — |
| F-15 | Reserved-offer lists (admin: all; seller: own) | AdminReservedOffersPage, UserReservedOffersPage | n/a: lists of status Zarezerowana | r03 | high | Q-06 |
| F-16 | Admin sends an inquiry to the offer's owner | StockSaleTab "Wyślij zapytanie" → `CreateInquiryAction` | W-07 | r03 | high | Q-07 |
| F-17 | Owner reads, accepts (reserves all) or rejects an inquiry | UserInquiriesPage | W-07 | r03 | high | Q-07 |
| F-18 | Admin inquiry list, details, delete | OfferInquiryResource | n/a: list and delete | r03 | high | — |
| F-19 | Offer journal "Mój dziennik": own entries, mark read, open offer, delete | UserJournalResource | n/a: entries come from BR-18 | r03 | high | Q-10 |
| F-20 | Bell notifications for offer events | Filament database notifications | n/a: see BR-18 map in r03 | r03 | high | Q-10 |
| F-21 | Delete, restore and permanently delete an offer | row/bulk/page Delete; "Przywróć ofertę"; "Usuń permanentnie", bulk "Usuń na zawsze" | W-09 | r01, r05 | high | — |
| F-22 | Duplicate an offer ("duplikuj") — the way to list another used piece | `Offer::getReplicateTableAction` | n/a: one-step copy, rules in BR-26 | r01 | high | Q-06 |
| F-23 | Gross price display and bulk price change | ViewOffer, tables, `ChangePriceBulkAction` | W-10 | r01, r05, spot | high | Q-15 |
| F-24 | Public gallery (max 10, main photo) and private photos | offer form, ViewOffer "select main image" | n/a: upload and ordering | r01 | high | — |
| F-25 | Offer version history (admin) | EditOffer revisions action, OfferRevisionsPage | n/a: read-only history | r01, r05 | high | Q-16 |
| F-26 | Admin offer tabs with counts; dashboard widgets "Moje oferty", "Ostatnie oferty"; offer stats on the user view | FullListOffer tabs, `OfferStatsOverview`, `LatestOffers`, ViewUser | n/a: counts and lists | r01, r05 | high | — |
| F-28 | Seller's "Moje oferty": own offers, filters, row actions (view, duplicate, activate draft), bulk price change | MyOffers, dashboard cards | n/a: list; actions are F-22, F-34, F-23 | r02 | high | — |
| F-29 | Admin "Wszystkie oferty": status tabs with counts, filters (status, sync, attributes, car, brand, rating, users, drafts, deleted), row and bulk actions (accept, price, delete, permanent delete, WooCommerce sync) | FullListOffer; old index "Najnowsze oferty (stary widok)" | n/a: list; actions are F-30, F-23, F-21 | r02 | high | — |
| F-30 | Admin accepts or rejects an offer (offer view, bulk) | AdminTab "Zatwierdź ofertę" / "Odrzuć ofertę"; bulk "Zaakceptuj oferty" | W-11 | r02, r01 | high | Q-03, Q-13 |
| F-31 | Create and edit an offer; fields depend on role and usage state | CreateOffer, EditOffer (one form; the wizard is dead) | W-01, W-02 | r02 | high | Q-13 |
| F-32 | Seller creates a missing producer inline (pending until approved) | create form "+" on "Producent szyby" | n/a: catalogue item, Vehicles dictionary approval | r02 | high | — |
| F-33 | Change history of the offer on its view (admin, changes log) | Shared ChangesRelationManager | n/a: read-only | r02 | high | Q-16 |
| F-34 | Save as draft and activate a draft | toggle "Zapisz ofertę jako szkic"; MyOffers "Aktywuj" | W-01 variant (F-03) | r02, r04 | high (unused in prod) | — |
| F-27 | Changing the company-account flag re-prices the seller's offers in WooCommerce | ProfilePage → `OfferUpdateService` → `OfferUpdateOffersChunk` | n/a: one trigger, BR-21 | r05 | high | Q-15 |

### F-01 Review ticket when a seller creates an offer

- **Purpose:** tell the admin that a seller published something that needs checking.
- **Description:** after a seller saves a new offer that is not a draft, the system matches the typed eurocode, copies attributes from a related offer, writes a journal entry for the seller, creates a ticket "Użytkownik utworzył ofertę" with a snapshot of the form, and notifies every admin ("Uzytkownik dodał nową ofertę!", buttons to the offer and the ticket).
- **Actors and permissions:** any authenticated non-admin creating an offer; admins and drafts produce no ticket (BR-01, BR-02).
- **Workflows · rules · dependencies:** W-01, BR-01, BR-02, BR-07, X-01, X-04.
- **Evidence:** r04; `OfferCreatedByUserAction.php:98-111, 223-256`; `CreateOffer.php:94`.
- **Status:** traced · **Confidence:** high.

### F-02 Review ticket when a seller edits an offer

- **Description:** after a seller saves an edit, the system re-matches the eurocode (BR-10), creates "Użytkownik edytował ofertę" with a snapshot and notifies the admins. For any editor, including admins, a "Nowa" offer is forced to rating 5.
- **Evidence:** r04; `OfferEditedByUserAction.php:290-419`. **Status:** traced · **Confidence:** high.

### F-03 Review ticket when a seller activates a draft

- **Description:** "Aktywuj" on a draft in "Moje oferty" moves a seller's offer to "Oczekuje zatwierdzenia" (an admin's straight to Aktywna), creates "Użytkownik utworzył ofertę (ze szkicu)" with the whole offer as snapshot, and notifies the admins.
- **Evidence:** r04; `MyOffers.php:205-213`, `Journal/ActivateFromDraftAction.php:20-71`. Prod: 0 such tickets, and 0 offers in Szkic or Oczekuje — the draft and pending paths are unused today.
- **Status:** traced · **Confidence:** high.

### F-06 Add or assign a eurocode from a ticket

- **Description:** a modal prefilled with the seller's typed code lets the admin enter the eurocode, OEM codes and compatible cars. The system creates the eurocode (or finds it), adds the cars to it, and replaces all of the offer's eurocodes with this one. The ticket itself is not changed.
- **Evidence:** r04; `ViewSubmission.php:176-211` (sync at :205), `EurocodeResource.php:57-89`. Catalogue side: Vehicles W-01.
- **Status:** traced · **Confidence:** high · **Open:** Q-02.

### F-07 Resolve a ticket

- **Description:** the admin picks Aktywna or Odrzucona and may add a message to the seller. The offer gets that status; the seller gets a journal entry and a notification; by default the ticket is hard-deleted. Disabled when the offer no longer exists or a newer ticket exists for the same offer.
- **Evidence:** r04, spot-check `ViewSubmission.php:453-500`.
- **Status:** traced, spot-checked · **Confidence:** high · **Open:** Q-01, Q-04.

## D. Business rules and domain knowledge

### Confirmed (`confirmed` / `observed`)

- **BR-01** Only non-admin actors produce review tickets; admin creates and edits produce none, and admins are the only recipients of the ticket notification — F-01, F-02; r04 `OfferCreatedByUserAction.php:98-111`, `User.php:143-148`.
- **BR-02** A draft offer produces no ticket until it is activated — F-01, F-03; r04.
- **BR-03** A ticket is outdated when a newer ticket exists for the same offer; an outdated ticket cannot be resolved (prod: 187 of 1213 are outdated) — F-05, F-07; r04 `ViewSubmission.php:130-142, 456`.
- **BR-04** Opening a ticket marks it read, silently (no model events) — F-05; r04 `ViewSubmission.php:73-82`.
- **BR-05** Resolving sets the offer to Aktywna or Odrzucona whatever its current status; there is no state guard and no transaction — F-07; r04, spot-check `ViewSubmission.php:477-494`. See Q-01.
- **BR-06** Accept writes the seller a journal entry "Oferta została zaakceptowana!" (type Success) and reject "Oferta została odrzucona" with "Powód odrzucenia: " + optional text (type Rejection), each followed by a database notification; skipped when the offer has no owner — F-07; r04 `Journal/OfferAcceptedAction.php`, `OfferRejectedAction.php`, `JournalAction.php:21-54`.
- **BR-07** The ticket is a snapshot. Its data is never applied to the offer; the offer already holds the seller's data from the moment of save — F-05, F-07; r04.
- **BR-08** Deleting an offer (soft delete, status Usunięta) leaves its tickets in place; the FK cascade runs only on a hard delete. Prod: 200 tickets point to deleted offers — r04, migration `2024_11_10_145443`.
- **BR-09** Only the Super Admin role may see tickets. Prod: Super Admin 3 users (12 submission permissions), User 28 users (none), Admin 0 users (none) — r04 `SubmissionPolicy.php`, manager query on `role_has_permissions`.
- **BR-11** Available quantity = quantity − sum of sold quantities − sum of reserved quantities; nothing is stored. The minimum quantity an editor may set is sold + reserved — F-08; r03 `HasSalesRecords.php:35-75`.
- **BR-12** Stock actions (reserve, sell, send inquiry, withdraw) appear only when available ≥ 1 and the offer is Aktywna or Zarezerowana — F-08; r03 `StockSaleTab.php:284`.
- **BR-13** After a reservation the offer becomes Zarezerowana only if quantity − reserved = 0 (sales ignored), otherwise Aktywna; after a sale it becomes Sprzedana only if quantity − sold = 0 (reservations ignored), otherwise Aktywna. Full sale also stamps the sold date and deactivates Otomoto adverts — F-09, F-12; r03, spot-check `OfferReservationChangeAction.php:36-42`, `OfferStockChangeAction.php:39`. See Q-06.
- **BR-14** Releasing a reservation hard-deletes it and sets the offer to Aktywna unconditionally, with no journal entry; only the reservation's author can release it or sell it — F-11, F-10; r03, spot-check `StockSaleTab.php:111-122`. See Q-08, Q-09.
- **BR-15** A sale return sets the sale row's quantity to 0 with a return label (the row stays as history), sets the offer to Aktywna, keeps the sold date, writes no journal entry. Admin only; the whole row — F-13; r03 `StockSaleTab.php:150-175`. Prod: 4 returns.
- **BR-16** Withdrawing sets Archiwalna, deactivates Otomoto adverts and tells the owner; reservations stay as rows — F-14; r03 `StockSaleTab.php:191-200`.
- **BR-17** An inquiry is addressed to the offer's owner, never records its sender, and goes Dostarczone → Przeczytane (on opening) → Zaakceptowane or Odrzucone, final. Accepting reserves all available stock (label "Rezerwacja z zapytania: …") and notifies all admins; rejecting notifies the admins. Inquiries cascade-delete with the offer — F-16, F-17; r03 `AcceptInquiryAction.php:19-64`, `RejectInquiryAction.php`, `InquiryStatusEnum.php`.
- **BR-18** Every offer event in the journal map (created, draft, activated, accepted, rejected, reserved fully/partly, sold fully/partly, withdrawn) writes a journal entry and a bell notification to the offer's **owner** (also when the owner is the actor); texts are hard-coded; nothing is written when the offer has no owner. Full map: r03 "Journal and notification map" — F-19, F-20; r03 `JournalAction.php:21-54`.
- **BR-19** The WooCommerce import creates the offer under a configured default owner and, for a sold product, a sale of the full quantity ("Status sprzedaży z wordpress"; 35 in prod), or for a reserved product a reservation of the full quantity; no journal — r03 `FetchWooProducts.php:138-153`. X-02.
- **BR-20** A seller's save of an offer sets "Oczekuje zatwierdzenia" (or Szkic if "save as draft"), whatever the previous status. An admin chooses the status on create (select, default Aktywna); on edit the admin has no status field, so the status stays as stored (an admin cannot move a draft out of Szkic by editing) — r02, manager check `OfferEditForm.php:52` (`getAdminHeader` commented out; r01 cited the unused method, settled as Q-19). Restoring a deleted offer also sets "Oczekuje zatwierdzenia" — r01, spot-check `EditOffer.php:48-58`, `CreateOffer.php:36-39`, `Offer.php:183-186`; r02 F-05, F-15, F-16.
- **BR-21** Gross price = round(price × 1.23, 2) unless the owner has a company account **and** accepted the seller's terms (then price as stored); with no owner, price as stored. The gross price is what panel lists, WooCommerce and Otomoto get. 1.23 is hard-coded. Changing the account type re-prices all the owner's synced offers — r01, spot-check `OfferPriceService.php:13-20`, `User.php:177-180`; r05 `ProfilePage.php:112-124`.
- **BR-22** Deleting an offer is a soft delete and sets status Usunięta in the same act (prod: all 288); permanent delete (admin, only for deleted offers) first removes the WooCommerce product, then the offer with its photos and versions; DB cascades remove its tickets, inquiries, adverts and sync rows, but reservations, sales and journal entries have no FK and are orphaned (prod: 38 orphan journal rows) — r01 `Offer.php:163-186`, r05 FK finding.
- **BR-23** Edit and delete buttons are shown only for Szkic, Aktywna, Odrzucona, Oczekuje, Wygasła; the edit page itself is guarded only by "admin or owner" (G-03). View: admins see everything incl. deleted offers; others only their own non-deleted offers — r01 `Offer.php:355-384`, `OfferPolicy.php`.
- **BR-24** SKU = "AG" + 8 random digits, generated on create and never changed; it is the offer's URL key. An admin may type one at creation; imported offers keep the WooCommerce SKU (483 of 2046 in prod) — r01.
- **BR-25** Quantity is editable only for usage "Nowa"; used glass is listed one piece per offer by duplicating. Duplication creates a new offer owned by the duplicating user, status Oczekuje, quantity 1, new SKU, producer and year cleared, eurocodes (with priorities), equipment, type and colour kept — r01 `OfferEditForm.php:343-352`, `Offer.php:608-657`.
- **BR-26** Rating (1–5) is entered only for Używana/Outlet; a "Nowa" offer is forced to 5 on a seller's edit — r01.
- **BR-27** Versions snapshot 9 fields (price, eurocode text, both descriptions, usage, rating, without-eurocode, alt eurocodes, year) on every change; status, quantity, attributes, eurocode links and photos are not versioned — r01 `Offer.php:125-146`.
- **BR-28** Every offer update (including a eurocode-link change, which touches the offer) pushes the offer to WooCommerce synchronously if it has a sync with auto-update and is not Szkic or Oczekuje; WooCommerce always sees one piece (stock 0/1, reserved flag). Sold, withdrawn and deleted offers deactivate their Otomoto adverts — r01 `Offer.php:156-227`, r05 `EurocodeOffer.php:14-22`.
- **BR-29** Publishing to WooCommerce requires a primary eurocode, a price > 0, an SKU, and type, producer and colour present and not "pending" in their dictionary; Otomoto additionally requires at least one car through the eurocodes — r01 `Offer.php:250-353`. Ties to Vehicles (catalogue approval).
- **BR-30** Nothing expires offers. "Wygasła" is never set and `expires_at` is never written (prod 0); the expiry command (archive past `expires_at`, notify admins) was unscheduled in July 2025 and deleted — r01, r05 git `f282532`, `968f8c4`.
- **BR-31** No status setter checks the current status, except the bulk accept (only Oczekuje/Odrzucona) and the visibility of the accept/reject buttons on the offer view — r01 transition table.
- **BR-32** Only Super Admin (3) and User (28) roles are assigned; the Admin role is empty. All gates in code test "is admin" (Admin or Super Admin). The User role holds exactly create, update, delete, view and view-any offer — r02 F-01, F-02, prod `role_has_permissions`.
- **BR-33** A seller sees only their own offers (lists, widgets, view page → 403 for others); the all-offers list and the offer cluster are admin-only — r02 F-01, F-33.
- **BR-34** Form by role: required are only price (≥ 1) and usage state. The seller types a free-text eurocode with suggestions (≥ 3 characters, code or OEM prefix, max 25); type and colour are shown to a seller only with "without eurocode" (then also a vehicle-information text), and the seller may choose only PRZEDNIA, TYLNA, BOCZNA or SZYBA DACHOWA. The admin picks eurocodes from the catalogue, type and colour, equipment and shipping method; year is always shown to the admin, to sellers only for used/outlet glass. Up to 10 public and 10 private photos; the first public one is the main photo — r02 F-07–F-11.
- **BR-35** A seller's bulk price change saves the new price without re-approval and pushes it to WooCommerce, unlike a single edit (BR-20) — r02 F-23, spot-check `MyOffers.php:99-101`, `BulkPriceUpdateSyncJob.php:34-42`. See Q-17.
- **BR-36** Approval buttons on the offer view depend on status: Oczekuje → accept and reject; Aktywna → reject; Odrzucona → accept; none otherwise. Rejection needs a reason. Bulk accept takes only Oczekuje and Odrzucona and skips the rest. Approval checks no data (eurocode, attributes) — r02 F-18, F-19.
- **BR-37** Duplicate is available to every role (no permission check, though the User role lacks the permission), creates no ticket, copies no photos — r02 F-28.
- **BR-38** (data) Within a eurocode, type, colour and equipment are practically constant (exceptions: 1 type, 6 colour — all coating variants, 16 equipment, 6 of them "nothing recorded"); producer, year and usage state vary. Year is filled on 590 offers, almost only used/outlet glass — r06 section C, manager re-run (204 groups by lowest priority: type 99.5%, colour 98.0%, producer 45.1%).
- **BR-39** (data) Offer creation dates are not publication dates: 1085 offers were created Sep 2025–Jan 2026 (import), and 140 of 317 sold offers show more than a year from creation to sale — r06 section D.
- **BR-10** When a seller edits an offer: "without eurocode" or an empty input detaches all eurocodes; an unknown code also detaches all of them; a new matching code replaces the others and triggers attribute copying — F-02; r04 `OfferEditedByUserAction.php:321-382`. Catalogue view in Vehicles W-01, W-03.

### Inferred or questionable

### Domain terms

- **Zgłoszenie (Submission):** in V1 an admin review ticket generated from a seller's create, edit or draft activation. Not a message from a customer and not a form. The name misleads; V2 naming is for grilling.
- **Dostępna ilość (available quantity), Rezerwacja, Sprzedaż, Zwrot sprzedaży, Wycofanie szyby:** stock vocabulary on the offer's "Zarzadzanie" tab. V1 calls withdrawing "Wycofaj szybę" — the UI already speaks of the glass, not the listing.
- **Zapytanie ofertowe (Inquiry):** a message from the admin to the seller asking to hold the glass; what real-world request stands behind it is not stored (Q-07).
- **Dziennik (Journal):** the owner's log of events on their offers, paired with a bell notification.
- **Rozwiązanie zgłoszenia:** an approval decision (accept or reject the offer), not a resolution of the ticket itself; the ticket only disappears if deleted.

## E. Functional workflows

### W-01 Seller creates an offer

- **Trigger:** "Utwórz" on the offer create screen (r02 covers the form).
- **Preconditions:** authenticated; the role User has `create_offer`.
- **Main flow:** 1. The offer is saved. 2. If it is a draft: journal entry only, stop. 3. Journal entry "offer created" for the owner. 4. Eurocode matching (also for admins, before the admin guard): unless "without eurocode" or already linked, the typed code is normalised and looked up in eurocode codes and OEM codes; the first hit is linked. 5. If linked, the latest related offer sharing a eurocode supplies its missing eurocodes and, if empty, colour, type and equipment (Vehicles W-02). 6. If the actor is not an admin: ticket "Użytkownik utworzył ofertę" with snapshot, `eurocode_match`, `connected_attributes`; notification to every admin.
- **State changes:** offer created; eurocode links and attributes possibly filled; ticket created unread.
- **Side effects:** journal entry for the seller, database notification to admins; the offer save hooks (r01).
- **Evidence:** r04; `OfferCreatedByUserAction.php:113-256`, `OfferEurocodeRelationService.php:15-82`. Prod: matched 447, not matched 765.

### W-02 Seller edits an offer

- **Main flow:** 1. The edit is saved. 2. For anyone: a "Nowa" offer gets rating 5. 3. For a seller only: eurocode re-matching per BR-10, attribute copying on a new match, ticket "Użytkownik edytował ofertę", notification to admins.
- **Evidence:** r04; `OfferEditedByUserAction.php:290-488`. Prod: 365 edit tickets.

### W-03 Resolve a ticket

- **Trigger:** "Rozwiąż zgłoszenie" on the ticket view.
- **Preconditions:** the offer exists and no newer ticket exists (BR-03); Super Admin (BR-09).
- **Main flow:** 1. Choose Aktywna or Odrzucona, optional message, "delete after" (default on). 2. The offer status is set and saved. 3. The offer save hooks run: queued Otomoto update (Aktywna only, dropped in V2) and a synchronous WooCommerce update when auto-update is on (X-02). 4. Journal entry and notification for the owner (BR-06). 5. The ticket is hard-deleted if chosen; the page reloads.
- **Error scenarios:** a WooCommerce failure shows a notification and is logged, the status change stays; no transaction.
- **Alternative paths:** the same accept/reject on the offer view (state-guarded) and the bulk accept; neither touches tickets (Q-04).
- **Evidence:** r04; stopped at the external WooCommerce and Otomoto calls.

### W-05 Reserve and release

- **Trigger:** admin "Zarezerwuj" (label required), owner "Zarezerwuj" (label fixed "Rezerwacja własciciela oferty"), owner accepting an inquiry (all available stock), WooCommerce import.
- **Preconditions:** BR-12; quantity 1..available, otherwise "Operacja nie powiodła się".
- **Main flow:** 1. A reservation row (author, quantity, label). 2. Status per BR-13 and a journal entry to the owner. 3. Release: author deletes the row; offer → Aktywna (BR-14).
- **Evidence:** r03; `StockSaleTab.php:252-278`, `UserReservationSection.php:33-71`, `OfferReservationChangeAction.php:18-45`.

### W-06 Sell and return

- **Trigger:** admin "Sprzedaj"; "sell reserved" by the reservation's author (deletes the reservation first, then sells); WooCommerce import.
- **Main flow:** 1. A sale row (quantity, sold at). 2. Status per BR-13, journal to the owner, full sale fires the sold event (Otomoto deactivation). 3. Return (admin): the row is zeroed and labelled; offer → Aktywna (BR-15).
- **Error scenarios:** "sell reserved" is not atomic: the reservation is gone before the sale is written.
- **Evidence:** r03; `StockSaleTab.php:98-175, 231-250`, `OfferStockChangeAction.php:21-45`. Whether a return re-syncs WooCommerce is left to r01.

### W-07 Inquiry

- **Main flow:** 1. Admin "Wyślij zapytanie" (title, optional rich text); the owner must exist. 2. Owner gets a bell "Nowe zapytanie ofertowe!" (button without a link). 3. Opening it marks Przeczytane. 4. Owner accepts (reserves all available, admins notified; if nothing is available, nothing is reserved but the message still says it was) or rejects (admins notified).
- **Evidence:** r03; `CreateInquiryAction.php:16-52`, `UserInquiriesTable.php`, `AcceptInquiryAction.php:19-64`.

### W-08 Withdraw

- **Main flow:** "Wycofaj szybę" → Archiwalna, archived event (Otomoto deactivation), journal to the owner. The modal promises a WordPress change; r01 to confirm via the save hook.
- **Evidence:** r03; `StockSaleTab.php:191-200`, `OfferArchivedAction.php:27-34`.

### W-11 Admin approval from the offer view or in bulk

- **Trigger:** "Zatwierdź ofertę" / "Odrzuć ofertę" on the "Administracja" tab; bulk "Zaakceptuj oferty" on "Wszystkie oferty" (also in "Moje oferty" for admins).
- **Preconditions:** admin; BR-36 status rules.
- **Main flow:** 1. Confirmation (reject: required reason). 2. Status Aktywna or Odrzucona, saved (WooCommerce push per BR-28). 3. Journal entry and notification to the owner (BR-06). 4. The page reloads; on the Pending-reject variant a leftover `dd()` halts the response after the change (Q-11).
- **Alternative paths:** the ticket's "Rozwiąż zgłoszenie" (W-03), without the status rules.
- **Evidence:** r02 F-18, F-19; r01 transition table.

### W-09 Delete, restore, delete permanently

- **Main flow:** 1. Delete (owner or admin, when editable): soft delete + Usunięta, deleted event (Otomoto deactivation), WooCommerce product set out of stock. 2. Restore (admin): status Oczekuje, saved (the restore modal wrongly says it cannot be undone). 3. Permanent delete (admin, deleted offers only, also in bulk with no status check): WooCommerce product deleted, offer removed with photos and versions; cascades per BR-22.
- **Evidence:** r01 `Offer.php:163-186`, `ViewOffer.php:79-84, 451-479`; r05 census rows 6–8.

### W-10 Bulk price change

- **Main flow:** 1. Admin picks offers and a mode: amount ±zł, percent ±%, fixed price (min 1). 2. Offers whose new price would be ≤ 0 are skipped with a count. 3. Prices saved quietly, then queued jobs in chunks of 15 re-save each offer so a version, a log entry and the WooCommerce push happen, and Otomoto adverts are updated.
- **Evidence:** r01 `ChangePriceBulkAction.php`, `BulkPriceUpdateSyncJob.php:28-48`.

### W-04 Add a eurocode from a ticket

- **Main flow:** 1. Modal prefilled with the typed code. 2. The code is normalised and the eurocode found or created with its OEM codes. 3. Selected cars are added to it. 4. If the offer exists, its eurocodes are replaced by this one. 5. Two notifications, the second shown even without an offer.
- **State changes:** catalogue (eurocode, cars) and the offer's eurocode links; the offer itself is not saved, so no sync runs.
- **Evidence:** r04; `ViewSubmission.php:176-211`.

## F. Cross-module dependencies

- **X-01** Vehicles (catalogue) — offer creation and editing match eurocodes and copy attributes from related offers; a ticket can create a eurocode and its car fitment. Offers depends on the catalogue; behaviour is specified in Vehicles W-01–W-03. r04.
- **X-02** WooCommerce — every offer save with auto-update pushes the offer synchronously (status change from W-03 included). r04, details in r01 and WooCommerce discovery.
- **X-03** Otomoto — queued advert update on save when Aktywna. Dropped from V2. r04.
- **X-05** WooCommerce import — creates offers, sales and reservations (BR-19). r03.
- **X-06** Users — the owner's company-account flag and accepted terms decide VAT (BR-21); changing it re-prices offers in WooCommerce (F-27). Owners are panel users with the role User (28 in prod). r01, r05.
- **X-07** Audit — V1 keeps three trails on offers (versions, changes log, activity log); V2 has the Audit module. r01, r05. See Q-16.
- **X-04** Journal and notifications — accept/reject and ticket creation write journal entries and database notifications. r04, details in r03.

## G. Ambiguities, contradictions and questionable behaviour

### Q-01 Resolving a ticket can force any offer status — `defect?`

- **Evidence:** r04, spot-check `ViewSubmission.php:477-494`: no check of the current status; a sold, reserved or deleted offer can be set to Aktywna or Odrzucona. The offer view's accept/reject is state-guarded (`AdminTab.php:100-170`).
- **Question for the user:** may approval change only an offer that waits for approval?
- **Resolution:** open.

### Q-02 "Dodaj eurokod" has three faults — `defect?`

- **Evidence:** r04 `ViewSubmission.php:192-209`: a) the form's unique rule rejects an existing code, so the action can only create, never assign an existing eurocode; b) `sync` drops the offer's other (alternative) eurocodes; c) "assigned to offer" is shown even when the offer is gone.
- **Question for the user:** in V2, should assigning a catalogue entry to an offer be its own action, separate from creating the catalogue entry?
- **Resolution:** open.

### Q-03 Approval is used, the ticket queue is not — `decide`

- **Evidence:** prod 1182 of 1213 tickets unread, yet 1721 "Oferta została zaakceptowana!" journal entries and 0 offers waiting: sellers' offers do go to Oczekuje (BR-20) and admins accept them quickly, from the offer screen or in bulk (r01, r03, r04).
- **Interpretations:** a) the ticket is redundant: the approval list (status Oczekuje) is the real queue; b) the ticket's value is the snapshot of what the seller typed and how the eurocode matched.
- **Question for the user:** does V2 keep pre-publication approval of every seller save, and is the "ticket" just the approval queue plus the change history (Audit)?
- **Resolution:** open.

### Q-04 Two doors to approval, tickets left behind — `defect?`

- **Evidence:** r04: approving on the offer view or by bulk action leaves the ticket unread and unresolved.
- **Resolution:** open; depends on Q-03.

### Q-06 Offer status derived from stock is inconsistent — `defect?`

- **Evidence:** r03, spot-check: the full-reservation test ignores sales and the full-sale test ignores reservations (BR-13); partial actions force Aktywna even when nothing is left; the reserved lists show only status Zarezerowana, so a partly reserved offer never appears there.
- **Interpretations:** a) bugs in a stock model that was meant for many pieces; b) irrelevant in practice: prod never had a partial reservation or sale.
- **Question for the user:** is an Offer one physical piece (status follows its single reservation/sale), or a stock of identical pieces (status derived from available quantity)? Ties to the domain grilling (quantity).
- **Resolution:** open.

### Q-07 What an inquiry stands for — `decide`

- **Evidence:** r03: admin → owner, no sender, no customer data; 20 rows in prod; accepting reserves everything.
- **Interpretations:** a) the admin relays a buyer's request to the seller to hold the glass; b) an internal "is it still available?" check.
- **Question for the user:** what is the real process, and does V2 keep it as a separate thing or as a reservation request?
- **Resolution:** open.

### Q-08 Undo actions force Aktywna and leave no trace — `defect?`

- **Evidence:** r03, spot-check `StockSaleTab.php:111-122, 150-175`: releasing a reservation or returning a sale sets Aktywna whatever the previous status or remaining rows; no journal entry; a return keeps the sold date.
- **Resolution:** open.

### Q-09 Reservations are private to their author — `defect?`

- **Evidence:** r03: only the author can release or sell a reservation; the owner's "Moje rezerwacje" shows only their own, so an admin's reservation on their offer is invisible to them yet reduces stock; an admin cannot release another person's reservation.
- **Resolution:** open.

### Q-10 Two unread channels for the same event — `decide`

- **Evidence:** r03: each event writes both a journal entry and a bell notification to the owner; prod journal 22 of 3571 read, bell 4932 of 6888 unread; journal policy is fully commented out (anyone with panel access sees only their own entries via the query filter).
- **Question for the user:** does V2 need a per-offer history for the seller, a notification, both, or does the offer's own history (Audit) cover it?
- **Resolution:** open.

### Q-11 Dead and debug code — `defect?`

- **Evidence:** r03, r04, r05: `PanelNotification` and `OfferNotificationHandler` never called; unreachable `FullListOfferTable` Livewire, `UserOfferCreateForm`, `OfferExtras`, offer factories and seeders, `UserNotification` list; `OfferPolicy::viewAny` reads the request URI (fails outside HTTP); duplicate taxonomy policy files; `bootstrap/providers.php` names a missing provider; SKU retry bug (r01); `dd($livewire)` after the Pending-offer reject in `AdminTab.php` (r01, r02, r03 agree on the bug, line numbers differ); admin edit cannot leave Szkic; tmp-session cleanup on edit is dead (`EditOffer::beforeCreate`); restore modal text; bulk-accept wording promises to process skipped offers; opposite draft-filter defaults in two lists; inquiry badge counts rejected inquiries; inquiry bell button without a link.
- **Resolution:** not ported; listed so the spec does not copy them.

### Q-12 Offer expiry — `decide`

- **Evidence:** BR-30: the status, the column and a form field exist; no mechanism since July 2025; prod has no data.
- **Question for the user:** drop expiry, or define a rule (for example: unsold after N days → withdrawn, with notice)?
- **Resolution:** open.

### Q-13 Re-moderation of every seller edit hides the change from live listings — `decide`

- **Evidence:** r01: a seller's save sets Oczekuje, and Oczekuje skips the WooCommerce push and the Otomoto refresh, so the listing shows the old data until an admin accepts; an Aktywna offer of a seller disappears from "Aktywne" while waiting.
- **Question for the user:** must every seller change be re-approved, or only changes to some fields (price, eurocode, photos)?
- **Resolution:** open.

### Q-14 Status vocabulary carries V1 mistakes — `ambiguous`

- **Evidence:** r01: the stored value "Zarezerowana" is misspelled (5 rows), displayed as "Zarezerwowana"; "Archiwalna" is displayed as "Wycofana"; Szkic, Oczekuje and Wygasła exist but have no rows.
- **Question for the user:** settled with the status model in grilling; the data migration maps the stored values.
- **Resolution:** open.

### Q-15 Price history and VAT follow the current state — `defect?`

- **Evidence:** r05 `User.php:110-130`: sales value = sold quantity × the offer's **current** price, not the price at sale (the sale row stores no price); r01/r05: VAT depends on the owner's current account type, so a change re-prices every offer retroactively, including in WooCommerce.
- **Question for the user:** should a sale record its price, and does a price follow the seller's VAT status at listing time or always the current one?
- **Resolution:** open.

### Q-16 Three audit trails on offers — `decide`

- **Evidence:** r01, r05: package versions (2923 rows, admin revisions page), the changes log (5996) and the activity log. V2 has Audit.
- **Question for the user:** is the V2 Audit history enough, or does the offer need a "revisions" view of its own (for example to compare what the seller changed before approval, Q-03)?
- **Resolution:** open.

### Q-17 Seller bulk price change skips re-approval — `defect?`

- **Evidence:** BR-35, spot-checked.
- **Question for the user:** is a price change something that needs approval? Decide together with Q-13.
- **Resolution:** open.

### Q-18 Is the seller's view of the catalogue intentionally blind? — `decide`

- **Evidence:** BR-33, BR-34: a seller cannot see other offers or the eurocode catalogue except as typing suggestions, and cannot pick type/colour unless they have no eurocode.
- **Question for the user:** in V2, does a seller pick a Glass from the catalogue (and only describe the piece), or keep typing a code? Belongs to the domain grilling.
- **Resolution:** open.

### Q-19 Admin status on edit — contradiction between r01 and r02, settled

- **Evidence:** r01 said an admin's edit keeps the status chosen in the admin select (`OfferEditForm.php:60-70`); r02 said the edit form has no status field. Manager check: `OfferEditForm.php:52` comments out `getAdminHeader`; the method r01 cited is unused.
- **Resolution:** r02 holds; BR-20 states it.

### Q-20 Colour and equipment conflicts within a eurocode — `decide`

- **Evidence:** r06: 6 colour conflicts (SOLAR / POWŁOKA GRZEWCZA / ZIELONA) and 16 equipment conflicts in 238 groups.
- **Interpretations:** a) entry noise (a fixed glass definition would remove it); b) real variants of one eurocode (the code's suffix should differ, or a piece-level override is needed).
- **Question for the user:** may an offer override a parameter of its Glass? Belongs to the domain grilling.
- **Resolution:** open.

### Q-05 Eurocode matching runs for admins too — `ambiguous`

- **Evidence:** r04: matching and attribute copying on create run before the admin guard, while the ticket is seller-only; on edit matching is seller-only.
- **Interpretations:** a) intended: matching is useful for anyone; b) accidental ordering.
- **Resolution:** open; folds into the Vehicles W-01/W-02 decisions.

## H. Missing information and gaps

| ID | What is unknown | Kind | Why | Resolution |
|---|---|---|---|---|
| G-03 | Whether the edit page (and revisions page) blocks sold, reserved or withdrawn offers beyond hiding the button | needs-runtime | r02 read the vendor code: only "admin or owner" is checked, so by URL the owner gets in (inferred) | low value: V2 enforces it in the handler |
| G-02 | Whether a sale return, a release or a withdrawal re-syncs WooCommerce | resolved | they call `$offer->save()` → `updated` hook → WooCommerce push per BR-28 | r01 (static) |
| G-01 | Whether the page 404s after "delete after resolve" reloads | needs-runtime | static reading only | open — low value, V2 redesigns the screen |

## I. Potential improvements

<!-- Changes to existing behaviour. Not requirements until a D- decision approves them. -->

- **IMP-01** One approval queue — tickets (F-01–F-07) duplicate the list of offers waiting for approval and are ignored → the waiting list is the queue, and the admin sees what the seller changed (from Audit) instead of a snapshot; value: one door instead of three, nothing left unresolved; decision: open (Q-03, Q-04, Q-16).
- **IMP-02** Guarded status transitions — every action sets any status (BR-31, Q-01, Q-06, Q-08) → each transition is allowed only from defined statuses, and reserve/sell/release/return derive the status from what is left; value: no forced or wrong statuses; decision: open.
- **IMP-03** A sale records its price — sales value uses today's price (Q-15) → the sale stores the net and gross price at sale time; value: correct sales history and reports; decision: open.
- **IMP-04** Stock changes leave a trace — release and return write nothing and "sell reserved" is not atomic (BR-14, BR-15, W-06) → every stock change is one atomic step with history and a notice to the owner; decision: open.
- **IMP-05** One re-approval rule — single edit re-approves, bulk price and duplicate do not (BR-20, BR-35, BR-37) → the same rule for every way a seller changes an offer, possibly limited to chosen fields (Q-13, Q-17); decision: open.
- **IMP-06** Publishing to external shops does not block or break the panel action — every save pushes synchronously to WooCommerce, failures are only logged (473 failed jobs) (BR-28) → publishing follows the offer's events, retries visibly, and a failure is shown on the offer; decision: open, with WooCommerce discovery.
- **IMP-07** One channel for the seller — journal and bell duplicate each other and are unread (Q-10) → one notification stream plus the offer's history; decision: open.

## J. Additional functionality to implement

### ADD-01 Seller picks a Glass, describes only the piece

- **Description:** the seller chooses a glass from the catalogue by eurocode (or VIN later); type, colour and equipment come from the glass; the seller enters only producer, year, usage state, rating, price, photos.
- **Value:** r06 shows the parameters are already constant per eurocode; removes copying from sibling offers (Vehicles W-02) and the blind free-text entry (Q-18).
- **Kind:** extends F-31 · **Dependencies:** domain grilling (Glass as an entity), Vehicles ADD-01.
- **Source:** manager (r06) · **Discussed:** no · **Decision:** open.

### ADD-02 Offer expiry rule

- **Description:** an unsold offer is withdrawn or flagged after a period, with notice to the seller.
- **Value:** the status and column exist and the business once wanted it (BR-30).
- **Kind:** entirely new (V1 feature removed) · **Source:** discovery (r01, r05) · **Discussed:** no · **Decision:** open (Q-12).

### ADD-03 Inquiries record who asks

- **Description:** an inquiry stores the requester (buyer or channel) and links to the reservation it causes.
- **Value:** today the admin relays a request with no record of the buyer (Q-07).
- **Kind:** extends F-16, F-17 · **Source:** manager · **Discussed:** no · **Decision:** open.

## K. Investigation log

| Report | Mission | Scope / question | Status | Key results | Limits | Follow-ups | Checked |
|---|---|---|---|---|---|---|---|
| r01 | local | Offer core: model, statuses, lifecycle, price/VAT, quantity, events, policy, jobs | returned, good | full status-transition table; no expiry; re-moderation; VAT; SKU; versions; hooks; F-21–25, BR-20–31, W-09–10, Q-12–16 | edit-route guard (G-03); `gallery`/`attachments` columns unused | G-03 → r02; Woo sync rows 2155 vs 2046 → WooCommerce discovery | spot: BR-20 re-moderation, BR-21 VAT |
| r02 | local | Offer screens and roles: create/edit/view/list, seller vs admin, approval, bulk actions, revisions, widgets | returned, good | roles in prod; field rules by role; approval doors; seller blind to others; bulk price bypass; F-28–34, BR-32–37, W-11, Q-17–19 | URL-level guards inferred (G-03); trashed filter default | producer approval → Vehicles (dictionary workflow) | spot: BR-35 bulk price; settled Q-19 |
| r06 | verify (data) | production data shape: catalogue (Vehicles G-09), offers per eurocode, attribute consistency within eurocode groups, sellers, timing | returned, good | glass parameters constant per eurocode, producer/year/usage vary; catalogue clean, dictionaries all Aktywna; `car_offer` redundant; creation dates = import; BR-38–39, Q-20 | oem_code vs oem_codes overlap not cross-tabbed | Vehicles G-09 closed | spot: section C re-run by manager (same result, 204 vs 238 groups by primary-eurocode definition) |
| r03 | local | Stock and transactions: reservations, sales, inquiries, journal, notifications | returned, good | stock derived; reservation/sale/return/withdraw/inquiry lifecycles; journal map (10 actions); F-08–20, BR-11–19, W-05–08, X-05, Q-06–11; prod: no partial stock ever | Woo re-sync after undo (G-02) | G-02 → r01; inquiry meaning Q-07 | spot: BR-13 reservation rule, BR-14 release |
| r04 | local + flow | Submissions: what they are, how created, how they change an offer | returned, good | tickets are seller-generated review tickets, not a public form (archive refuted); F-01–07, BR-01–10, W-01–04, X-01–04, Q-01–05; prod shapes | runtime reload; Otomoto queue (dropped) | `dd()` at `AdminTab.php:127` → r02; Admin permissions → settled by manager query (BR-09) | spot: BR-05 resolve without guard; creation guard |
| r05 | cross-repo | vocabulary across V1 + entry-point census | returned, good | 74-row census; expiry command deleted (git); company-account re-price; FK/orphan map; failed jobs 473 (Otomoto, Woo); F-26–27, X-06–07 | runtime items (missing provider, `UserNotification` list) | `car_offer` 1531 rows → Vehicles data-shape (G-09 there); failed sync handling → WooCommerce discovery | relies on r01 spot-checks (same hooks) |

## L. Completeness assessment

- **Exit criteria:**
  1 ✓ — the 74 census rows (r05) map to F- items, or to WooCommerce discovery (Woo sync and import rows), Vehicles (taxonomies, eurocodes, cars), Otomoto (dropped), or the dead-code list Q-11.
  2 ✓ — every F- item has entry points, evidence and confidence; each has a W- workflow or a written reason (lists, displays, derived values).
  3 ✓ — "matched, not examined" files are out of scope (WooCommerce, Otomoto, Vehicles) or unreachable (commented routes, dead forms).
  4 ✓ — leads followed: producer approval → Vehicles; `car_offer` and Vehicles data → r06; Woo sync surplus (2155 vs 2046) and 473 failed jobs → WooCommerce discovery; role grants → settled by query (BR-09, BR-32).
  5 ✓ — r01 vs r02 (admin status on edit) settled as Q-19; r06 vs manager group counts differ only by the primary-eurocode definition.
  6 ✓ — each report has a manager spot-check of a high-impact claim (BR-05, BR-13/14, BR-20/21, BR-35, r06 section C); approval, stock and price rules are confirmed by at least two reports.
  7 ✓ — wave 2 (r06) found no new F- items or entry points.
  8 ✓ — IMP-01–07, ADD-01–03.
- **Mapped:** 34 F, 39 BR, 11 W, 7 X, 20 Q, 3 G, 7 IMP, 3 ADD.
- **Verified:** approval and status rules (r01, r02, r04 + spot), stock (r01, r03 + spot), price/VAT (r01, r02, r05 + spot), data shape (r06 + re-run).
- **Still uncertain:** G-01, G-03 (runtime, low value); Q-07 (meaning of inquiries) and the `decide` items need the user.
- **Weak coverage:** WooCommerce payloads and import details (deliberately left to WooCommerce discovery); runtime behaviour of the panel (no running V1).
- **Ready because:** every V1 way an offer is created, changed, approved, reserved, sold, withdrawn and deleted is traced, and the data shows how the features are really used.

## M. Decision log
