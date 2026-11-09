# r02 — local: Offer screens and capabilities by role

Question: What can each kind of user (admin vs seller/regular user, any other role) do with offers through the panel, and under which conditions? · V1 commit: 511ce3b

All paths are relative to `/home/kp/laravel/projekty/AllGlass/app/Filament/` unless they start with `app/` or `vendor/`. Abbreviations: **OCF** = `Clusters/Offers/Resources/OfferResource/Components/OfferCreateForm.php`, **OEF** = `.../Components/OfferEditForm.php`, **AT** = `.../Components/AdminTab.php`, **CO** = `.../Pages/CreateOffer.php`, **EO** = `.../Pages/EditOffer.php`, **VO** = `.../Pages/ViewOffer.php`, **MO** = `Pages/MyOffers.php`, **FLO** = `Pages/FullListOffer.php`.

## Summary

- Only two roles exist in use in prod: `Super Admin` (3 users) and `User` (28 users). No `Admin` role is assigned to anyone, although `User::isAdmin()` accepts `Admin` or `Super Admin`. "Admin" below means `isAdmin()`. Seller means any non-admin.
- A seller adds an offer through ONE form (OCF); the "wizard" form `UserOfferCreateForm` is dead code. A non-draft seller save always becomes `Oczekuje zatwierdzenia` (Pending) and creates a Submission plus an admin database notification. A draft save skips both.
- Only an admin approves or rejects: from the Administracja tab on the offer view, from the bulk action "Zaakceptuj oferty" on the all-offers list, or from the Submission screen (r04). Rejection requires a reason, and the seller gets a journal entry plus notification.
- A seller sees only own offers (MyOffers, widgets, view policy). There is no seller-facing list of other sellers' offers: `FullListOffer` is admin-only, and the Livewire `FullListOfferTable` is unreachable.
- Any seller edit of a non-draft offer sends it back to Pending (re-verification) and files a Submission "Użytkownik edytował ofertę". 
- Admin and seller screens differ mainly by field visibility: the admin sees status, shipping, equipment, the eurocode multi-select and the type/colour pickers; the seller sees a free-text eurocode with suggestions and (only for non-standard glass) type/colour.
- Notable defects/anomalies: `dd($livewire)` after rejecting a Pending offer (F-18), admin cannot change status in the edit form (F-16), seller can open edit/revisions pages by URL for statuses the button hides (F-14, F-29), replicate resets status to Pending without a Submission (F-28).

## Findings

### Access model and navigation

#### F-01 The Offers cluster ("Baza zasobów") and the "Wszystkie oferty" list are admin-only
- Tag: confirmed · Impact: high (permissions)
- What happens: The cluster `Offers` (title "Baza zasobów", slug `glass`) is visible only if `isAdmin()`. `FullListOffer` ("Wszystkie oferty") is also admin-only. The resource index (`admin/glass/offers`, nav label "Najnowsze oferty (stary widok)") is allowed only for admins by a policy hack that matches the route URI.
- Evidence: `Clusters/Offers/Offers.php:26-29 Offers::canAccess`; `Pages/FullListOffer.php:74-77 canAccess`; `app/Policies/OfferPolicy.php:21-27 viewAny` (route-uri check `admin/glass/offers` → isAdmin, otherwise true).

#### F-02 Offer permissions per role (policy + DB)
- Tag: confirmed · Impact: high
- What happens: Policy `update` / `delete` / `view` = admin or the offer's owner. `create` needs permission `create_offer`; `forceDelete`, `restore`, `replicate`, `deleteAny` etc. are permission-based. Production role→permission: `User` has exactly `create_offer, delete_offer, update_offer, view_any_offer, view_offer`; `Super Admin` has all `*_offer` permissions plus `page_Offers`, `widget_LatestOffers`, `widget_OfferStatsOverview`. `model_has_permissions` is empty (no per-user overrides). Note `delete_any_offer`, `force_delete_*`, `restore_*` and `replicate_offer` are not held by `User`.
- Evidence: `app/Policies/OfferPolicy.php:29-123`; prod query on `role_has_permissions` (2026-11 dump) listing the above; `model_has_permissions` count 0.

#### F-03 Seller entry points: "Moje oferty", dashboard widgets, "Dodaj nową ofertę"
- Tag: confirmed · Impact: normal
- What happens: "Moje oferty" (`MyOffers`) has no `canAccess`, so everyone (admins too) can open it. It lists only offers where `user_id` = current user (soft-deleted ones are hidden). The dashboard shows two widgets for every user, both scoped to own offers: `OfferStatsOverview` (cards "Moje oferty:" with "W tym miesiącu: N", "Aktywne oferty" / "Oczekujące oferty: N", "Odrzucone oferty", each linking to MyOffers pre-filtered by status) and `LatestOffers` ("Ostatnie oferty", row action "Zobacz"). The `LatestOffers` header buttons differ by role: admin gets "Zobacz pełny widok" (→ FullListOffer) and "Dodaj ofertę"; seller gets "Moje oferty" and "Dodaj ofertę".
- Evidence: `Pages/MyOffers.php:44-62` (query `where user_id = Auth::id()`, header action "Dodaj nową ofertę"); `Resources/OfferResource/Widgets/LatestOffers.php:35,92-117`; `.../OfferStatsOverview.php:155-203`; `Providers/Filament/AdminPanelProvider.php` (widgets registered for all users).

### Creating an offer

#### F-04 A seller (or admin) creates an offer on one form; the multi-step wizard is dead code
- Tag: confirmed · Impact: normal
- What happens: `CreateOffer` uses `OfferCreateForm::form` (a single page, section "Nowa oferta"). `UserOfferCreateForm` (a 5-step wizard "Ogólne / Specyfikacja i wygląd / Galeria zdjęć / Połącz z pojazdem / Dodatkowe", with its own eurocode/alt/OEM/vim inputs) is never referenced anywhere in `app/`. Not part of V2 behaviour.
- Evidence: `Resources/OfferResource.php:108-113 form()`; grep `UserOfferCreateForm|getFormWizard` finds only the class itself (`Components/UserOfferCreateForm.php:30,42,71`).

#### F-05 Status on create: draft toggle, seller → Pending, admin → chosen (default Active)
- Tag: confirmed · Impact: high (state)
- What happens: The toggle "Zapisz ofertę jako szkic" (default off) is on every create. If on: status `Szkic` (Draft) for everyone. Otherwise a seller's offer gets `Oczekuje zatwierdzenia` (Pending), while an admin keeps the value of the admin-only select "Status oferty" (options: all 9 statuses, default `Aktywna`). Toggling shows a toast "Oferta zostanie zapisana jako Szkic" / "Oferta zostanie zapisana jako Aktywna".
- Evidence: `Pages/CreateOffer.php:383-393 mutateFormDataBeforeCreate`; `OCF:52-64 getAdminHeader` (Section "Ustawienia oferty", visible admin only, select default `OfferStatus::Active`); `OCF:118-142 getDraftSwitcher`.

#### F-06 After a seller creates a non-draft offer: Submission + admin notification + journal entry
- Tag: confirmed · Impact: high (external/state)
- What happens: In `afterCreate`, `OfferCreatedByUserAction` runs: (1) tries to match the typed eurocode to a catalogue eurocode (see Vehicles W-01/W-03) and copies colour/type/equipment from a related offer when empty (Vehicles W-02, `OfferCreatedByUserAction.php:99-167`); (2) Draft → `DraftOfferCreatedAction` (journal entry "Dodałeś nowy szkic" to the owner, a 1-minute cache flag showing the notice "Świetnie! Dodałeś szkic oferty. Możesz ją aktywować dokonując edycji na liście swoich ofert") and STOPS: no Submission, no admin notification; (3) otherwise `OfferCreatedAction` (journal "Dodałeś nową ofertę" + notice "Świetnie! Dodałeś kolejną ofertę! Po sprawdzeniu jej poprawności, trafi do katalogu allGlass."); (4) if the actor is not an admin: creates a `Submission` titled "Użytkownik utworzył ofertę" with `original_data` = form data plus eurocode-match info, and sends every admin a database notification "Uzytkownik dodał nową ofertę!" with buttons "zobacz-ofertę" and "zobacz-zgłoszenie". An admin creating an offer gets none of (4).
- Evidence: `Actions/OfferCreatedByUserAction.php:33-57,169-204`; `Actions/Journal/OfferCreatedAction.php:20-26`; `Actions/Journal/DraftOfferCreatedAction.php`; `Actions/Abstracts/JournalAction.php:101-134` (journal entry + `notifyNow` to the offer owner); `Pages/CreateOffer.php:431-444`.

#### F-07 Required fields and validation on create/edit
- Tag: confirmed · Impact: normal
- What happens: Required: "Cena" (numeric, min 1, `stripCharacters(',')`), "Stan szyby" (usage_status). Everything else is optional in the form: no photo and no eurocode are required. Max 10 photos in "Zdjęcia oferty" (image files, reorderable; the first one is the main photo; hint "Pierwsze zdjęcie w galeri zostanie wybrane jako główne zdjęcie oferty") and max 10 in the private gallery. "Cena brutto" is shown as required but is `disabled` (display only).
- Evidence: `OCF:152-179` (gallery), `OCF:234-249` (price), `OCF:319-329` (usage_status required), `OCF:483-494` (private gallery), `OEF:268-298`.

#### F-08 Photo handling: public gallery vs private gallery; main photo choice
- Tag: confirmed · Impact: normal
- What happens: Two media collections: `offer-gallery` ("Zdjęcia oferty", shown publicly/in lists) and `offer-private-gallery` ("Prywatne zdjęcia", labelled "Uwaga! Prywatne zdjęcia nie zostaną opublikowane w ofercie. Będą one widoczne wyłącznie dla Ciebie."). Drag-and-drop reordering rewrites the order column; the first image is the main photo. On the offer view an admin (only) gets the hint action "Wybierz główne zdjęcie oferty" (image picker slide-over); selecting swaps `order_column` with the current first image and shows "Zapisono!" / "Zmieniono główne zdjęcie oferty". Everyone who can see the offer sees the gallery ("Kliknij zdjęcie aby powiększyć") and the private gallery section "Prywatne". Lists show the first `offer-gallery` image or a placeholder.
- Evidence: `OCF:152-179,483-494`; `OEF:139-165,475-485`; `VO:368-384` (admin-only picker action), `VO:499-518 selectImage`, `VO:407-412,489-494`.
- Note: `VO:372-374` picker action body only reads `selected_image` and does nothing; the swap happens via the Livewire listener `selectImage` fired from the picker component.

#### F-09 Eurocode inputs: the admin and the seller see different controls; "without eurocode" path
- Tag: confirmed · Impact: normal (details in Vehicles W-01, W-03)
- What happens:
  - Toggle "Nie posiadam eurocodu / kodu OEM" (`without_eurocode`) is on every create/edit form (live).
  - Admin: multi-select "Eurocody (Admin)" (`eurocodes`, SelectEurocodesPartial:28-32, visible admin) and NO plain eurocode text input (it is `visible(!isAdmin)` and also hidden by the toggle; both conditions must hold — inferred from Filament's combined hidden/visible semantics).
  - Seller: free text "Eurocode / kod OEM" (helper "Wpisz identyfikator szyby") with a datalist of suggestions from catalogue eurocodes (needs ≥3 chars after upper-casing and stripping spaces; matches code prefix OR any OEM code prefix; max 25), hidden when the toggle is on. On the edit form sellers also see a disabled multi-select "Przypisane eurokody" (the codes the admin/matcher attached, ordered by priority; not saved).
  - With the toggle on: the eurocode input is hidden; the textarea "Informacje o pojeździe" appears (helper "Jeśli nie posiadasz eurokodu, podaj dostępne informacje, które mogą pomóc w identyfikacji szyby."), and for sellers "Rodzaj szyby" and "Barwa szyby" become visible (otherwise hidden for sellers).
  - On create the matcher is skipped for `without_eurocode`; on seller edit with the toggle on the stored eurocode is wiped and all attached eurocodes are detached.
- Evidence: `OCF:182-232`; `OEF:168-266`; `Actions/OfferCreatedByUserAction.php:59-97`; `Actions/OfferEditedByUserAction.php:267-274`; `Components/Partials/SelectEurocodesPartial.php:28-32,78`.
- Prod: 1 of 2046 offers has `without_eurocode = 1`. Admin list has a tab "Bez eurocodu" for them (F-21).

#### F-10 Field visibility by "Stan szyby" (usage_status: Nowa / Używana / Outlet)
- Tag: confirmed · Impact: normal
- What happens: "Stan szyby" is a button group (Nowa green, Używana warning, plus Outlet). 
  - "Ilość szyb" (quantity, default 1, min 1) is visible ONLY for `Nowa`; helper: "Pole dostępne wyłącznie dla „Nowych” szyb. W przypadku szyb używanych skorzystaj z funkcji „duplikuj” na liście ofert." So used/outlet glass is one offer per physical item (quantity stays DB default 1).
  - "Ocena stanu szyby" (rating 1-5 stars, default 1 in the form; DB default 5) and "Krótki opis szyby" (description) visible only for `Używana` or `Outlet`; rating is not submitted for `Nowa`.
  - "Rok produkcji" (year) visible for Używana/Outlet, or always for admin.
  - On seller edit of a `Nowa` offer rating is forced to 5.
  - On edit the minimum quantity = sold + reserved (so quantity cannot go below what is already committed).
- Evidence: `OCF:319-365`; `OEF:315-360` (`minValue(fn($record) => $record->getMinimumQuantity())`); `Filament/Traits/HasSalesRecords.php:44-47`; `Actions/OfferEditedByUserAction.php:256-264` (runs for admins too: any save with usage_status Nowa and rating ≠ 5 sets rating 5); migration `database/migrations/2024_10_06_151733_create_offers_table.php:36,40` (quantity default 1, rating default 5).

#### F-11 Type / colour / producer / equipment / shipping / SKU rules by role
- Tag: confirmed · Impact: normal
- What happens:
  - "Rodzaj szyby" and "Barwa szyby": visible to admin always; to a seller only when "without eurocode" is on. A seller can only pick type names `PRZEDNIA`, `TYLNA`, `BOCZNA`, `SZYBA DACHOWA` (active types, sorted by usage count). Colour: active colours sorted by usage. Otherwise these are filled from a matched sibling offer (Vehicles W-02).
  - "Producent szyby": visible to everyone, searchable. On CREATE the list is active producers plus ones created in the current session, and has a "+" create-option form (name, hidden slug, hidden `tmp_session` = current session id, status select for admin only, default Pending for sellers). On EDIT the list is all producers whose status ≠ Pending and there is no create option.
  - "Metoda wysyłki" (shipping) and "Dodatkowe wyposażenie" (equipment multi-select; helper "Uwaga! Pozostaw to pole puste, jeżeli chcesz, aby zostało automatycznie wypełnione na podstawie eurocode" on create): admin only.
  - "(Admin) Kod SKU": always hidden on create (`hidden(true)` overrides `visible(admin)`), so sellers and admins never type it; SKU is auto-generated with prefix `AG` on create. Not shown in edit form. Visible read-only (copyable) in the admin tab.
  - "Notatki własne" (notes): note says only the owner sees them; the offer view section "Prywatne" shows them.
- Evidence: `OCF:368-430,447-471,265-272`; `OEF:363-460`;  `Models/Offer.php skuOptions()` (`->using('AG')`, `generateOnCreate(true)`, `refreshOnUpdate(false)`); `AT:387-393`.
- Contradiction: `visible()` and `hidden()` set on the same component (OCF:194-197, 269-270) — outcome relies on Filament combining them (hidden if either says hidden). Confirmed by behaviour of `CanBeHidden` in vendor only for forms-level wrapper; treat the exact combination as `inferred`.

#### F-12 tmp_session mechanism (pending producer/equipment created inline)
- Tag: confirmed · Impact: normal
- What happens: Inline-created producers/types/colours/equipment carry `tmp_session` = the creator's session id so they appear in that creator's dropdown while still Pending. On create-save: the chosen producer's `tmp_session` is cleared (so it persists), then all other producers with `tmp_session` = this session are DELETED (abandoned inline creates). Equipment: the same, but only if equipment ids were selected (and equipment can only be selected by admins, with no inline create in the current forms, so this branch is effectively inert). The equivalent `beforeCreate` method on `EditOffer` is never called by Filament's EditRecord (it has `beforeSave`, not `beforeCreate`), so no tmp_session cleanup happens on edit — and the edit form has no create-option anyway.
- Evidence: `Pages/CreateOffer.php:395-430`; `Pages/EditOffer.php:532-565` (method name `beforeCreate` on an `EditRecord` page); `OCF:298-301,415-430`.
- Unreferenced leftover: if the user abandons the form without saving, the Pending producer with their `tmp_session` stays until another create-save by the same session id (inferred). A seller-created producer remains Pending until an admin reviews it (producer/type/colour status is checked by the sync guard `canBeWooSynch`, Offer.php ~330-348: "Typ szyby - oczekuje" etc.).

#### F-13 Price: net price entered; gross display = net × 1.23 for private (non-company) owners
- Tag: confirmed · Impact: high (money)
- What happens: The seller/admin enters "Cena" (zł). A non-editable "Cena brutto" is calculated live (net × 1.23 rounded to 2 dp) unless the owner has a company account (`company_account` and `has_accepted_terms`), in which case the gross field is hidden and gross = net. On create the multiplier depends on the logged-in user; on edit on the offer owner. Lists (MyOffers, FullListOffer) show the gross price with the net price struck through (`<del>`) for private accounts and plain PLN for company accounts. The admin view of an offer shows the warning "Uzytkownik tej oferty nie zaznaczył oświadczenia sprzedawcy." for non-company owners, and the tab "Administracja" shows "Rodzaj konta": "Firmowe"/"Prywatne". Prod: 14 users are flagged `company_account=1` (of 31).
- Evidence: `app/Services/Offer/OfferPriceService.php getAdjustedOfferPrice`; `OCF:234-263`; `OEF:268-298`; `VO:128-132,240-247`; `Components/Tables/MyOffersTableSchema.php:294-310`; `FLO:428-444`; `AT:364-370`; `app/Models/User.php:177-180 isCompanyAccount`.
- Ambiguous: whether the stored `price` is net for company accounts and "price_gross" is never stored (field is disabled, so not submitted) — see Could not establish.

### Editing

#### F-14 Who can edit which offer and when: view-page button vs. real authorisation
- Tag: confirmed (button) / inferred (URL access) · Impact: high (state)
- What happens: On the offer view, the "Edytuj" button: for admin or for any Draft offer it goes straight to the edit page (hidden if trashed); for a seller with a non-draft offer it shows only when status ∈ {Szkic, Aktywna, Odrzucona, Oczekuje zatwierdzenia, Wygasła} (`canBeEdited`) and asks for confirmation: heading "Pamiętaj!", text "Jeśli dokonasz zmian w ofercie, będziemy musieli ponownie ją zweryfikować." Reserved, Sold, Archived and Deleted offers cannot be edited via buttons (same `canBeEdited` gates the "Usuń" actions in lists too). However the edit route itself is only protected by the policy `update` (owner or admin), so an owner can open `/edit` for a Sold/Reserved/Archived offer by URL (inferred from Filament `EditRecord::authorizeAccess` + `OfferPolicy::update`; not verified at runtime).
- Evidence: `VO:423-448 getEditAction`; `Models/Offer.php:355-366 canBeEdited`; `app/Policies/OfferPolicy.php:54-58`; `VO:60-65 authorizeAccess` (view: admin or owner, trashed hidden from non-admin, `Models/Offer.php:368-382 canBeViewByUser`).

#### F-15 Edit save: seller → Pending + Submission + admin notification; draft toggle → Draft
- Tag: confirmed · Impact: high (state)
- What happens: On edit the toggle "Zapisz ofertę jako szkic" starts ON when the offer is a Draft. Save rules: toggle on → status `Szkic`; else seller → status `Oczekuje zatwierdzenia` (even from Aktywna: the offer goes back for verification); else admin → status unchanged. After save, for a non-admin actor: `OfferEditedByUserAction` re-runs the eurocode matcher (without_eurocode → clear eurocode and detach all; empty input → detach; no match → detach and record `matched:false`; match not yet attached → replace with the matched code and pull attributes from a sibling offer — Vehicles W-01/W-02), creates a `Submission` "Użytkownik edytował ofertę" (`original_data` = form data) and notifies all admins ("Użytkownik edytował ofertę", buttons "zobacz-ofertę" / "zobacz-zgłoszenie"). The Submission/notification are created even if the seller saved it as draft (no draft check). After saving, the user is redirected to the offer view.
- Evidence: `Pages/EditOffer.php:501-514,568-571,491-494`; `Actions/OfferEditedByUserAction.php:236-254,267-365`; `OEF:101-126`.
- Defect?: editing a Draft as seller with the draft toggle switched off sets Pending via the normal branch, but no "created" submission is generated, only an "edited" submission (the seller activating from the list uses a different path, F-17).

#### F-16 Admin edit form has no status field; admin cannot move a Draft out of Draft by the toggle
- Tag: defect? · Impact: normal
- What happens: `OfferEditForm::getAdminHeader` (the admin-only "Status oferty" select) exists but is commented out in `form()`. In `mutateFormDataBeforeSave` an admin with the toggle off has no status branch, so status stays as stored. Consequences: an admin editing a Draft with toggle off keeps status `Szkic`; an admin cannot set any status through the edit form (statuses are changed only by Accept/Reject, archive/sell/reserve, delete/restore). On create, the admin does have the status select.
- Evidence: `OEF:50-55 (getAdminHeader commented out)`; `Pages/EditOffer.php:501-514`; contrast `OCF:44`.

#### F-17 Seller "Aktywuj" activates own draft: Pending + Submission, with journal entry
- Tag: confirmed · Impact: high (state)
- What happens: In "Moje oferty" the row action "Aktywuj" (visible only if status = Szkic; modal "Aktywacja oferty", buttons "Aktywuj"/"Anuluj") calls `ActivateFromDraftAction`: owner admin → `Aktywna`, otherwise `Oczekuje zatwierdzenia`; for non-admin owners creates a Submission "Użytkownik utworzył ofertę (ze szkicu)" and notifies all admins ("Uzytkownik dodał nową ofertę! (ze szkicu)"). The owner gets a journal entry "Aktywowałeś ofertę!" ("Gratulacje! Udało Ci się aktywować ofertę. Teraz zostanie ona zweryfikowana przez administratora pod kątem poprawności danych. Otrzymasz powiadomienie, gdy status Twojej oferty ulegnie zmianie.") and then is redirected to the offer view.
- Evidence: `Pages/MyOffers.php:205-217`; `Actions/Journal/ActivateFromDraftAction.php:18-77`; `Actions/Abstracts/JournalAction.php:101-134`.
- Note: the "Aktywuj" action exists only in MyOffers, not in the all-offers list; an admin can convert a Draft only by that action on own offers (or via editing, which cannot do it, F-16).

### Approval flow

#### F-18 Admin approves / rejects from the offer view ("Administracja" tab); buttons depend on current status
- Tag: confirmed · Impact: high (state)
- What happens: The tab "Administracja" (admin only; also shows owner link, account type, phone, "Data wygaśnięcia", SKU) offers: status `Oczekuje zatwierdzenia` → "Odrzuć ofertę" and "Zatwierdź ofertę"; `Aktywna` → "Odrzuć ofertę" only; `Odrzucona` → "Zatwierdź ofertę" only; no buttons for other statuses. "Zatwierdź": confirmation "Czy na pewno chcesz zatwierdzić tę ofertę?" / "Uzytkownik zostanie poinformowany, że jego oferta została zaakceptowana" → status `Aktywna`. "Odrzuć": confirmation + required textarea "Powód odrzucenia" (placeholder "Wpisz powód odrzucenia oferty...") → status `Odrzucona`. Both write a journal entry to the offer owner and send them a database notification with a "zobacz" button: Accepted: "Oferta została zaakceptowana!" / "Twoja oferta została zweryfikowana i zatwierdzona przez administratora. Nowy status oferty: Aktywna"; Rejected: "Oferta została odrzucona" / "Niestety nie możemy opublikować Twojej oferty. Powód odrzucenia: <reason>". Saving the status fires the model `updated` hook (events + Woo sync — r01).
- Evidence: `AT:407-484 getAdminOfferActions`; `Actions/Journal/OfferAcceptedAction.php:16-26`; `Actions/Journal/OfferRejectedAction.php:46-58`; `Actions/Abstracts/JournalAction.php:101-134`.
- Defect: for a Pending offer the reject action ends with `->after(fn($livewire) => dd($livewire))` — rejecting from Pending dumps/halts the response after the status was already changed (`AT:429`); the Active/Rejected variants reload the page properly.
- Not checked on approval: no guard that type/producer/colour are set or non-Pending (that guard exists only for Woo sync, `canBeWooSynch`), no eurocode required.

#### F-19 Bulk "Zaakceptuj oferty" (admin, all-offers list): only Pending/Rejected are accepted
- Tag: confirmed · Impact: high (state)
- What happens: In the bulk menu of the all-offers list (FLO) an admin selects offers → "Zaakceptuj oferty" (modal "Akceptacja ofert"). The modal splits the selection: "Pominięte oferty: n / m" (those whose status is not Pending or Rejected, with the text "Poniższe oferty nie mogą zostać zaakceptowane. Czy mimo wszystko chcesz kontynuować operację?") and "Oferty do dodania: n". Only the processable ones are set to `Aktywna` with the same journal entry + notification to each owner as F-18; skipped ones are untouched (the wording "mimo wszystko" suggests they would be processed, but they are not). Toast: "Pomyślne zmieniono statusy ofert" is sent before processing. It is also wired into MyOffers for admins (filtering own offers only).
- Evidence: `Components/AcceptOffersBulkAction.php:24-93`; `app/Services/Offer/Aggregates/OfferStatusAggregate.php:11-19 canOfferBeAccepted`; `FLO:158`; `MO:95`.

#### F-20 Approval from the Submission screen
- Tag: confirmed · Impact: high (state)
- What happens: The Submission view (r04) also calls `OfferAcceptedAction` / `OfferRejectedAction` with a reason, which gets appended to the owner's journal text.
- Evidence: `Filament/Resources/SubmissionResource/Pages/ViewSubmission.php:483,486`. (Out of scope; r04.)

### Listing, filtering, bulk actions

#### F-21 All-offers list (admin): tabs, filters, columns, row and bulk actions
- Tag: confirmed · Impact: normal
- What happens: Tabs: "Wszystkie", "Oczekujące", "Aktywne", "Sprzedane", "Wycofane" (= Archiwalna), "Bez eurocodu", each with a count badge. Search across SKU, eurocodes, producer, type, usage. Default sort newest first; modal "Filtruj" (persisted in session): "Status", "Synchronizacja z WP" (Wszystkie / Tylko zsynchronizowane / Bez synchronizacji), "Synchronizacja z OTOMOTO" (Otomoto dropped from V2), "Typ szyby", "Producent", "Kolor", "Stan używania", "Połączona z pojazdem" (Tylko połaczone / Bez pojazdu), "Marka pojazdu" (offers linked to a car of that brand), "Liczba gwiazdek (rating)" 1-5, "Użytkownicy" (multi), "Uwzględnij drafty" (ternary: drafts hidden unless "yes"), "Usunięte" (trashed filter, default ON = see deleted too? see note). Optional grid layout via a toggle button. Row action group: Podgląd, replicate, Edytuj and Usuń (only when `canBeEdited`). Bulk group: delete, "Zmień cenę", Otomoto/WP bulk actions, "Synchronizuj z wordpress", "Zaakceptuj oferty", and "Usuń na zawsze" (force-deletes and first removes the product from WooCommerce). Sold rows are highlighted. Columns: Nr oferty (SKU), Zdjęcie, Eurocody, Otomoto/WordPress sync icons (hidden by default), Dostępna ilość: "available/total", Producent, Cena, Status, Dodał (owner initials, link to the user), Typ szyby, Stan szyby (+rating), Data.
- Evidence: `FLO:88-114,116-183,190-294,296-348,350-497`.
- Note: "Uwzględnij drafty" has `blank` = hide drafts in FLO (drafts need an explicit "yes"), but in MyOffers `blank` = show drafts (MyOffers:119-125), i.e. opposite defaults. The trashed filter is `->default(true)` in FLO (`FLO:193-194`), meaning the default selection is the filter's "with trashed"/"only trashed" state — exact default semantics depend on Filament's TrashedFilter; `unknown` whether deleted offers appear by default (prod has 288 trashed, all with status `Usunięta`).

#### F-22 "Moje oferty" for a seller: own offers, filters, row actions, bulk price change
- Tag: confirmed · Impact: normal
- What happens: Columns: Nr oferty, Zdjęcie, Eurocody, Otomoto (hidden), "Dostępna ilość:" (available/total), Status (labels: Szkic, Oczekuje, Aktywna, Odrzucona, Wygasła, Wycofana, Zarezerwowana, Sprzedana), Cena (gross + struck net for private accounts), Producent szyby, Typ szyby (hidden), Data. Search placeholder "eurocode, nr OEM lub SKU". Clicking a row opens the offer view. Filters ("Filtruj", persisted in session): Status (initial value taken from the `?status=` URL query — used by dashboard cards), Typ szyby / Producent / Kolor (options limited to values used on the seller's own offers), Stan używania, Liczba gwiazdek, "Uwzględnij drafty". Row actions: "Podgląd", replicate, "Aktywuj" (drafts). Empty state: "Nie masz jeszcze ofert" / "Wszystkie dodane przez Ciebie oferty pojawią się tutaj. Dodaj jedną!" with button "Dodaj ofertę". Seller bulk action: only "Zmień cenę" (F-23); no bulk delete for sellers. An admin on the same page gets bulk delete, Otomoto/WP, Woo sync and "Zaakceptuj oferty" instead of price change.
- Evidence: `MO:54-104,106-161,196-219`; `Components/Tables/MyOffersTableSchema.php:238-336`.
- Note: the filter "Użytkownicy" is defined but not included in the filter form (MO:156-159 vs the schema at 163-194).

#### F-23 Bulk price change ("Zmień cenę"): sellers on MyOffers, admins on the all-offers list
- Tag: confirmed · Impact: high (money)
- What happens: Modal "Zbiorcza zmiana ceny": type "Kwota (±zł)" (default), "Procent (±%)" or "Cena stała (zł)" and an amount (required; for fixed min 1). New price = price + amount, price × (1 + amount/100), or the fixed value, rounded to 2 dp. Offers whose new price would be ≤ 0 are skipped (warning "Pominięto n ofert – nowa cena wyniosłaby 0 zł lub mniej."); the rest are saved quietly (no `updated` event) and then `BulkPriceUpdateSyncJob` is dispatched in chunks of 15 (presumably pushing price to WP/Woo — r01/r03). No Submission/Pending reset: price changes by this action do NOT send the offer back for verification (unlike a single edit, F-15). Sellers only see it on their own offers; there is no cap on percent decrease/increase.
- Evidence: `Components/ChangePriceBulkAction.php:111-216`; `MO:99-101`; `FLO:154`.
- Defect?/decide: a bulk price change by a seller bypasses the re-approval that a normal edit triggers.

#### F-24 Delete / restore / force-delete
- Tag: confirmed · Impact: high (deletion)
- What happens:
  - Delete (soft): visible on the offer view ("Usuń" header action), the edit page header (`Actions\DeleteAction`) and list rows, when `canBeEdited` (status Szkic/Aktywna/Odrzucona/Oczekuje/Wygasła). Allowed for owner or admin (policy `delete`). After the view-page delete the user is redirected to FLO (admin) or MyOffers (seller). On soft delete the model hook sets status `Usunięta`, `deleted_at`, fires `OfferDeletedEvent` and the sync (r01).
  - Bulk delete: sellers have none; admins have "Usuń zaznaczone oferty" in FLO/MyOffers.
  - Restore: "Przywróć ofertę" (view page, admin only, only if trashed; modal text says "Ta akcja jest nieodwracalna…", which is misleading) → the restored offer is set to `Oczekuje zatwierdzenia` (model hook).
  - Permanent delete: "Usuń permanentnie" (view, admin only, trashed only): modal "Czy na pewno chcesz permanentnie usunąć ofertę?" / "Ta akcja jest nieodwracalna, nie będzie można przywrócić usuniętej oferty"; first removes the product from WooCommerce, then force-deletes (media cleared by hook). Bulk "Usuń na zawsze" in FLO. Policy `forceDelete` needs permission `force_delete_offer` (held by Super Admin only).
- Evidence: `VO:67-86,450-475`; `Pages/EditOffer.php:480-489`; `Models/Offer.php:156-190`; `FLO:150-166`; `app/Policies/OfferPolicy.php:63-107`.

#### F-25 Old resource index "Najnowsze oferty (stary widok)" (admin-only)
- Tag: confirmed · Impact: normal
- What happens: Inside the cluster, the table "Najnowsze oferty" shows date, photo, eurocodes, "Dostępna ilość:", owner initials ("Dodał"), status, price ("Cena"), WP sync icon; filters "Uwzględnij drafty", Typ szyby, Producent, Kolor, Stan używania, Rating, "Synchronizacja z WP", trashed; row actions: view, replicate, edit/delete when `canBeEdited`; no bulk actions. Header "Create" action. The navigation badge shows the total offer count (including trashed, since the soft-delete scope is removed). Superseded by FLO; no separate behaviour.
- Evidence: `Resources/OfferResource.php:82-106,128-235,279-326,342-348`; `Pages/ListOffers.php:666-677`.

### Offer view

#### F-26 What each role sees on the offer view
- Tag: confirmed · Impact: normal
- What happens: Allowed: admin always; owner unless trashed (others get 403). Everyone sees "Informacje ogólne" (Przypisane Eurocody with a reorder action, "Dostępna ilość:" available/total, "Status oferty:" (user-friendly name, e.g. "Oczekuje na zatwierdzenie"), "Cena:", "Cena brutto:" (hidden for company owners), "Opis oferty:", "Data dodania:"), "Wygląd i specyfikacja" (type, producer, colour with colours showing warning for Pending taxonomy values, year, usage status badge, "Kondycja szyby (rating): n/5", main photo, vehicles "Pojazdy:" / "Brak powiazanych pojazdów", "Dodatki" / "Brak powiązanych dodatków", gallery) and "Prywatne" (notes, private gallery). Seller-only: "Moje rezerwacje" block (F-27). Admin-only: "(Admin) Kod od użytkownika:", "(Admin) Kod OEM:", "Informacja od użytkownika" (user_description), tabs "Administracja" (F-18), "Zarządzanie" (stock/sales, r03), WooCommerce sync tab, Otomoto tab (dropped), warnings: "Oferta sprzedana. Brak możliwości zarzadzania stanem i rezerwacjami" (sold), "Uzytkownik tej oferty nie zaznaczył oświadczenia sprzedawcy.". A non-admin sees "Eurocode / Kod OEM:" (the typed value) only when no catalogue eurocode is attached.
- Evidence: `VO:60-65,89-162,164-276,279-419,478-497`; `AT:338-404`; `Models/Offer.php:368-382`.

#### F-27 Seller reservation/stock controls on own offer (pointer to r03)
- Tag: confirmed · Impact: high (state)
- What happens: On the view of an own offer a non-admin owner sees "Moje rezerwacje" (date, quantity, actions "Oznacz jako sprzedane" and "Usuń rezerwacje") and a "Zarezerwuj" button (quantity 1..available; visible only if `allowedToStockChange`: available ≥ 1 and status Aktywna or Zarezerowana). "Usuń rezerwacje" sets the offer status straight back to `Aktywna` regardless of other reservations (defect?). Details of reservations/sales belong to r03.
- Evidence: `Components/UserReservationSection.php:507-596`; `Filament/Traits/HasSalesRecords.php:52-70`.

### Replication, revisions, history

#### F-28 "Replicate" (duplicate) an offer: reachable by every role; resets to Pending
- Tag: confirmed (behaviour) / inferred (no authorisation gate) · Impact: high (state)
- What happens: Row action (label from Filament's default; the form's helper text calls it "duplikuj") available in MyOffers (seller), FLO, old index, admin reservations list. It copies the offer for the CURRENT user (`user_id` = actor), forces `quantity = 1`, `status = Oczekuje zatwierdzenia`, and does not copy: `sku` (a new one is generated), producer, `image`, year, `status`, `expires_at`, `archived_date`, `sold_date`, `notes`, `deleted_at`. After saving it copies equipment ids and the eurocodes with priorities, shows "Pomyślnie skopiowano ofertę", and redirects to the edit page of the copy. Photos are not mentioned (model replicate does not copy media). No Submission or admin notification is created for a replica; it becomes Pending silently until the seller saves the edit (which then creates an "edited" Submission, F-15), while an admin's replica stays Pending until approved (the edit does not change status for admins). This is the only way to create several used-glass items of the same type (quantity field is hidden for used/outlet).
- Evidence: `Models/Offer.php:608-651 getReplicateTableAction`; `Resources/OfferResource.php:216`; `MO:203`; `FLO:142`; `OCF:357` (helper text). `vendor/filament/actions/src/ReplicateAction.php` has no authorisation call, so the missing `replicate_offer` permission for the `User` role does not hide it (inferred).

#### F-29 Revisions ("Wersje"): admin button, but route open to the owner
- Tag: confirmed (button) / inferred (route) · Impact: high (data)
- What happens: Offers keep snapshot versions (library "versionable", SNAPSHOT strategy) of: price, eurocode, user_description, description, usage_status, rating, without_eurocode, alt_eurocodes, year (not status, user, timestamps). The edit page header shows the `RevisionsAction` only to admins; it opens `/{record}/revisions` where the user can browse previous/next version and "restore" (reverts to the previous version, then redirects to the edit page). The revisions page is authorised only by `canEdit(record)` (policy: owner or admin), so the owner can reach it by URL (inferred from `vendor/mansoor/filament-versionable/src/RevisionsPage.php:128-131`). Restoring a version does not change status and does not create a Submission.
- Evidence: `Pages/EditOffer.php:486-487`; `Clusters/Offers/Pages/OfferRevisionsPage.php`; `Resources/OfferResource.php:337`; `app/Models/Offer.php:125-146`; vendor `RevisionsPage.php:122-131`.
- Note: the version list and the admin-only "Historia zmian oferty" (ChangesRelationManager, F-30) are separate.

#### F-30 "Historia zmian oferty" relation manager on the offer view (admin only; not on edit)
- Tag: confirmed · Impact: normal
- What happens: Read-only list of change-log entries of the offer, with a slide-over detail "viewWithNav"; delete actions exist on rows and as a bulk action (admin can delete log lines). Shown only to admins and never on the edit page.
- Evidence: `Resources/Shared/ChangesRelationManager.php:27-73`; `Resources/OfferResource.php:119-124`.

### Other screens listing offers

#### F-31 Relation managers: offers shown on User, Car, Submission, and offer taxonomy pages
- Tag: confirmed · Impact: normal
- What happens: `Shared/OffersRelationManager` ("Powiązane oferty") lists offers on pages of users, cars, submissions and on taxonomy views (producer/type/colour/equipment): columns photo, eurocodes, "Dodał", status, price (PLN), usage status, type, WP sync; filters status, Stan używania, Synchronizacja z WP, rating, users, "Cena minimalna"/"Cena maksymalna" (zł), trashed; row action "Zobacz"; bulk delete. The separate `UserResource/RelationManagers/OffersRelationManager` and `CarResource/RelationManagers/OffersRelationManager` (with "edytuj"/"Zobacz" row actions, a "Rodzaj szyby" filter, create/edit inline forms asking for "name") are not registered anywhere that I found (User/Car pages register the Shared one): `unreachable` (inferred from grep; CarResource registration line is commented).
- Evidence: `Resources/Shared/OffersRelationManager.php:35-241`; grep of `OffersRelationManager` in `Resources/UserResource.php:22`, `Resources/CarResource.php:5,15,119`, `Resources/UserResource/Pages/ViewUser.php:32`, `Resources/CarResource/Pages/ViewCar.php:48`.

#### F-32 Reservation lists (pointer to r03)
- Tag: confirmed · Impact: normal
- What happens: "Zarezerwowane" lists: sellers see `UserReservedOffersPage` (own Reserved offers, nav badge = their count), admins see `AdminReservedOffersPage` ("Zarezerwowane oferty (admin)", badge = all Reserved, filter "Pokaż rezerwacje tylko innych użytkowników", "Użytkownicy"). Details in r03.
- Evidence: `Pages/UserReservedOffersPage.php:48-60`; `Pages/AdminReservedOffersPage.php:36-45`; `Components/Tables/AdminReservedOffersTable.php:230-268`.

#### F-33 What a seller sees of other sellers' offers: nothing
- Tag: confirmed · Impact: high (permissions)
- What happens: No screen lets a seller list others' offers. `FullListOffer` is admin-only (F-01); the Livewire `App\Livewire\FullListOfferTable` (an all-offers table without role limit) is referenced only from a Blade view (`full-list-offer-page.blade.php`) that no page uses, so it is `unreachable`. A direct URL to someone else's offer view returns 403 (`canBeViewByUser`, policy `view`). Sellers also have `view_any_offer` permission but the resource index uses the route-uri hack and cluster access check (F-01). Exact behaviour of a seller requesting `/admin/glass/offers` is `inferred` as denied.
- Evidence: `Pages/FullListOffer.php:74-77`; `app/Livewire/FullListOfferTable.php:55-113` (no access check; query is all offers); `resources/views/filament/clusters/offers/offers/resources/offer-resource/pages/full-list-offer-page.blade.php:3` (only user of `@livewire('full-list-offer-table')`; grep over `app resources routes` finds no page using this view); `Models/Offer.php:368-382`.

## Entry points
Not a cross-repo mission; omitted.

## Contradictions and defects
- D-1 `AT:429`: `->after(fn($livewire) => dd($livewire))` on the Pending-state reject action (F-18).
- D-2 `OEF:50-55`: admin status select commented out, so no status control on edit and a Draft cannot be activated by an admin through edit (F-16).
- D-3 `EO:532` `beforeCreate()` on an EditRecord page is dead; tmp_session cleanup never runs on edit (F-12).
- D-4 Bulk-accept modal text "Czy mimo wszystko chcesz kontynuować operację?" for skipped offers, but skipped are never processed (F-19).
- D-5 "Przywróć ofertę" modal says the action is irreversible (copy-paste from permanent delete) (`VO:466-475`).
- D-6 Seller bulk price change bypasses re-verification; seller `Usuń rezerwacje` forces Active (F-23, F-27).
- D-7 Admin-gated buttons (revisions, edit for Sold/Reserved) are not backed by route-level checks (F-14, F-29).
- D-8 `visible()` and `hidden()` set on the same fields (`eurocode`, `sku`) (F-09, F-11).
- D-9 Inconsistent "drafty" filter defaults between FLO (hide) and MyOffers (show) (F-21).
- D-10 `OfferResource` creates a producer option with status select for admin only; for sellers the status default is a closure on a hidden field (`OCF:306-310`) so it is likely not submitted: the actual stored status for a seller-created producer is `unknown` (depends on the DB column default).

## Leads outside scope
- `Actions/Stock/OfferStockChangeAction`, `OfferReservationChangeAction`, `OfferArchivedAction`, `CreateInquiryAction` (r03): sell/reserve/archive/inquiry on the offer view (StockSaleTab "Wycofaj szybę", "Wyślij zapytanie", "Sprzedaj", "Zarezerwuj", "Zwrot sprzedaży").
- `Resources/SubmissionResource/Pages/ViewSubmission.php:483-486` (r04): third approval entry point.
- `Jobs/Offer/BulkPriceUpdateSyncJob`, `Services/ApiService/Actions/BulkSynchProductToWooAction`, `Models/Offer.php afterUpdateSyncAction` (r01/WooCommerce): what the price/status changes push externally.
- `OtomotoOfferResource/Components/*` (BulkAdvertCreateAction, BulkRemoveFromWpAction, OtomotoSynchronizationTab): dropped from V2.
- `Services/Offer/OfferEurocodeRelationService` (`findEurocodeFromUserInput`, `findRelatedOfferByEurocodes`): Vehicles W-01..W-03.
- `Clusters/Offers/Resources/OfferTaxonomies/*` (type/colour/producer/equipment resources) and `EurocodeResource`: how Pending taxonomy items created by sellers are approved (listed in cluster, not read here).
- Widget/page permissions `widget_*` / `page_Offers` in `role_has_permissions` suggest Shield was once used; Shield is dropped (roadmap) — all gating in code is `isAdmin()`.

## Could not establish
- Whether deleted offers show in FLO by default (`TrashedFilter->default(true)`): needs a running panel; behaviour depends on Filament's trashed-filter default value semantics.
- Stored value of price for company vs private accounts (is `price` net, is gross stored anywhere, e.g. in `price_gross` column): `price_gross` input is disabled so not dehydrated; whether the column exists and who fills it is r01/model territory.
- Whether a seller can really open `/admin/glass/offers/{id}/edit` for Sold/Reserved/Archived offers and `/revisions`: derived from code and vendor, not run.
- Status stored for a producer created inline by a seller (`tmp_session` set, status default?): not checked in the migration.
- Label text of the replicate row action (Filament default, locale-dependent): not located in `lang/`.
- Whether abandoned `tmp_session` producers are ever cleaned: no scheduler search was done (cross-repo r05).

## Coverage ledger
- Searched: `UserOfferCreateForm|getFormWizard`, `OfferPolicy`, `Versionable`/revisions, `OfferAcceptedAction|OfferRejectedAction`, `OffersRelationManager`, `ReservedOffersTableSchema|AdminReservedOffersTable|ReservedOfferFilters`, `FullListOfferTable|full-list-offer-table`, `canAccess`, `getReplicateTableAction`, `canBeEdited|canBeViewByUser|getMinimumQuantity|allowedToStockChange`, `isAdmin|isCompanyAccount|getAdmins`, `visible|hidden` interplay in `vendor/filament/forms`, DB: roles, role_has_permissions, offers per user, status counts, trashed count, without_eurocode count, company_account count (aggregates only, no personal data).
- Examined (in full unless noted): `Clusters/Offers/Offers.php`; `Clusters/Offers/Resources/OfferResource.php`; Pages `CreateOffer`, `EditOffer`, `ListOffers`, `ViewOffer`; `Clusters/Offers/Pages/OfferRevisionsPage.php`; Components `OfferCreateForm`, `UserOfferCreateForm`, `OfferEditForm`, `AdminTab`, `AcceptOffersBulkAction`, `ChangePriceBulkAction`, `WooSyncBulkAction`, `UserReservationSection`, `Tables/MyOffersTableSchema`; `Pages/FullListOffer.php`; `Pages/MyOffers.php`; `app/Livewire/FullListOfferTable.php` + `resources/views/livewire/full-list-offer-table.blade.php`; `Resources/OfferResource/Widgets/LatestOffers.php`, `OfferStatsOverview.php`; `Actions/OfferCreatedByUserAction.php`, `OfferEditedByUserAction.php`; `Resources/Shared/OffersRelationManager.php`, `Resources/UserResource/RelationManagers/OffersRelationManager.php`, `Resources/CarResource/RelationManagers/OffersRelationManager.php`, `Resources/Shared/ChangesRelationManager.php`; hop-out: `Actions/Journal/{OfferAccepted,OfferRejected,OfferCreated,DraftOfferCreated,ActivateFromDraft}Action.php`, `Actions/Abstracts/JournalAction.php`, `Policies/OfferPolicy.php`, `Models/Offer.php` (hooks, `canBeEdited`, `canBeViewByUser`, replicate action, versionable config), `Models/User.php` (`isAdmin`, `isCompanyAccount`), `Traits/HasSalesRecords.php`, `Services/Offer/OfferPriceService.php`, `Services/Offer/Aggregates/OfferStatusAggregate.php`, `Enums/OfferStatus.php`, `Components/Partials/EurocodeTextColumn.php`, `vendor/mansoor/filament-versionable/src/RevisionsPage.php`, `Providers/Filament/AdminPanelProvider.php`.
- Matched, not examined fully: `Components/StockSaleTab.php` (only skimmed for action names/labels; r03 owns), `Components/Tables/ReservedOffersTableSchema.php`, `Tables/AdminReservedOffersTable.php`, `Filters/ReservedOfferFilters.php`, `Pages/UserReservedOffersPage.php`, `Pages/AdminReservedOffersPage.php` (reservation screens, r03), `Components/Partials/SelectEurocodesPartial.php` and `ReorderEurocodesViewAction.php` (only visibility lines read; Vehicles discovery W-01 covers), `Resources/OfferTaxonomies/*`, `Offers/Resources/EurocodeResource.php`, `ShippingMethodResource` (out of scope), `Resources/SubmissionResource/Pages/ViewSubmission.php` (r04), `OtomotoOfferResource/*` (dropped), `Utils/PanelConfig.php` (navigation config, grepped only).
