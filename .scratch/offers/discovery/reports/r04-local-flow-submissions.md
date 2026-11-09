# r04 — local + flow: Submissions ("Zgłoszenia") and their processing

Question: What is a Submission in V1, who creates it, what it carries, what an admin does with it, and what happens to offer / eurocodes / attributes on ViewSubmission · V1 commit: 511ce3b

## Summary
- A Submission is an automatic review ticket for an admin, written when a non-admin user creates, edits or activates (from draft) an offer in the Filament panel. It is NOT a public form and NOT an API/WordPress/import artefact. Four code sites create it; all are in `app/Filament/Actions/`. No API route, controller, job or DB::table writes to it.
- It carries a fixed title, user_id, offer_id, `original_data` (JSON snapshot of the form data plus eurocode matching results) and `is_read`. `message` is never written (0 of 1213 rows).
- The admin reads it (opening marks it read), sees what the user typed and how the eurocode was matched, can add/assign a eurocode to the offer, and "Rozwiąż zgłoszenie": set offer status to Aktywna or Odrzucona (journal entry + notification to the owner), optionally deleting the submission.
- Processing trace ends at: Offer `updated` hook -> queued Otomoto listener and synchronous WooCommerce update (external, not investigated), plus the owner's journal and DB notification.
- Prod (allglass_v1_prod): 1213 submissions, 1026 distinct offers, 2025-11-12 .. 2026-08-06; 31 read, 1182 unread; all have a user; 848 "created", 365 "edited", 0 "from draft".

## Findings

### Submission is created when a non-admin user creates an offer (not draft)
- Tag: confirmed · Impact: normal
- What happens: after CreateOffer saves, `OfferCreatedByUserAction` runs. For a Draft offer it only calls DraftOfferCreatedAction and returns (no submission). Otherwise it writes a journal entry (OfferCreatedAction), and if there is no authenticated user or the user is admin it stops; else it creates Submission title "Użytkownik utworzył ofertę", user_id from form data, offer_id, original_data = form data (+ `eurocode_match`, + `connected_attributes`) and sends admins a DB notification "Uzytkownik dodał nową ofertę!" with buttons `zobacz-ofertę` and `zobacz-zgłoszenie`.
- Evidence: app/Filament/Clusters/Offers/Resources/OfferResource/Pages/CreateOffer.php:94 afterCreate calls the action; app/Filament/Actions/OfferCreatedByUserAction.php:98-111 (draft/admin guards), :223-235 createSubmission, :237-256 notification to `User::getAdmins()` (roles Admin / Super Admin, app/Models/User.php:143-148).

### Submission is created when a non-admin user edits an offer
- Tag: confirmed · Impact: normal
- What happens: after EditOffer saves, `OfferEditedByUserAction`: (always, even admin) forces rating=5 when usage_status is "Nowa" and saves quietly; admin/no user -> stop. Otherwise re-matches the eurocode and creates Submission "Użytkownik edytował ofertę" + admin notification "Użytkownik edytował ofertę". Eurocode side effects of the edit are listed in the next finding.
- Evidence: EditOffer.php:116-118 afterSave; OfferEditedByUserAction.php:290-308, :310-318, :384-397, :399-419.

### Submission is created when a non-admin user activates a draft
- Tag: confirmed · Impact: normal
- What happens: MyOffers row action "Aktywuj" (visible only for status Draft) runs ActivateFromDraftAction: status becomes Pending ("Oczekuje zatwierdzenia") for a non-admin owner, Active for an admin owner (no submission). For non-admin: Submission "Użytkownik utworzył ofertę (ze szkicu)" with original_data = whole offer ->toArray() (so no `eurocode_match` key), notification "Uzytkownik dodał nową ofertę! (ze szkicu)". Owner gets journal "Aktywowałeś ofertę!".
- Evidence: app/Filament/Pages/MyOffers.php:205-213; Journal/ActivateFromDraftAction.php:20-71. Prod has 0 such rows (titles only the two other variants), so this path was never exercised or the draft flow was not used.

### These are the only creation sources
- Tag: confirmed · Impact: normal
- What happens: no public form, API, WordPress/Woo webhook, import or command creates submissions. routes/api.php has only `/user`; no tests, factories or DB::table references. The archive-scan claim "created from a public-facing form" is refuted: the "user" is a panel user with role User creating offers in the Filament panel (policy "User" role has create_offer/update_offer).
- Evidence: grep for Submission / submissions / zgłoszen across app, routes, config, database, resources, tests, bootstrap: only the 3 actions above + SubmissionResource/Policy/Model + seeders; routes/api.php:1-8; database/seeders/ShieldSeeder.php (User role has create_offer, update_offer, no submission permission). The other Journal/* files only `use` Submission (unused import). git: introduced in commit 10b8e3d "Submission and action".

### Eurocode auto-matching and attribute copying at creation (recorded into original_data)
- Tag: confirmed · Impact: high (changes offer data)
- What happens (create, non-draft, any user incl. admin, because verifyRecordData/handleRelationsByEurocode run before the admin guard): 
  1. If `without_eurocode` or the offer already has eurocodes -> no matching.
  2. Else user input `eurocode` is normalised (trim, upper, whitespace removed) and looked up in Eurocode.code or Eurocode.oem_codes (JSON contains); first hit wins. Hit -> offer eurocodes sync to that one, `eurocode_match={matched:true,input,eurocode:{id,code}}`. Miss -> `eurocode_match={matched:false,input}` and nothing attached.
  3. If the offer now has eurocodes, find the latest other offer (any non-Pending status, incl. soft-deleted) sharing any of them; record `connected_attributes.related_offer_id`; attach that offer's missing eurocodes (priority = max+10 steps, existing untouched; recorded `eurocodes_added`); copy colour and type only if empty (recorded `color`,`type`); copy equipment only if the offer has none (recorded `equipment`); save quietly.
- Evidence: OfferCreatedByUserAction.php:113-151, :153-221; Services/Offer/OfferEurocodeRelationService.php:15-27 (lookup), :56-75 (related offer), :80-82 normalizeInput. Prod: eurocode_match matched=true 447, false 765, key absent 1; connected_attributes present in 164 rows.

### Eurocode handling on user edit (non-admin only)
- Tag: confirmed · Impact: high
- What happens: `without_eurocode` -> clear `eurocode` text and detach all eurocodes. Empty input -> detach all. No match -> detach all and record matched=false (this removes a previously attached eurocode when the user types an unknown code). Match already attached -> record matched=true only. Match new -> `sync` to just that eurocode (replaces others), record, and copy related-offer attributes the same way as above (here related offer is looked up with withTrashed, no Pending filter).
- Evidence: OfferEditedByUserAction.php:321-382, :422-488. Note the edit path does not set `connected_attributes` unless a related offer exists.

### Submission list: unread badge, mark read, delete; no create/edit/filters
- Tag: confirmed · Impact: normal
- What happens: navigation item "Zgłoszenia" (sort 6) shows a badge with the count of `is_read = false` (empty when 0). Table columns: eye icon (grey = read, "Wiadomośc odczytana"; warning = unread, tooltip "Oznacz jako przeczytaną"; clicking it sets is_read true, no-op if read), "Data zgłoszenia", "Tytuł zgłoszenia" (searchable), "Użytkownik" (links to user view), "Identyfikator", "Sku". Default sort newest first. No filters. Row action "Zobacz". Bulk: delete (DeleteBulkAction), "Oznacz jako przeczytane" (saveQuietly). Create/Edit pages exist as classes but their routes are commented out; the form schema is empty -> unreachable (CreateSubmission, EditSubmission). ListSubmissions has no header actions.
- "Identyfikator" column: `Brak` if without_eurocode; the typed input (green = matched, red = unmatched, blue if no match info) ; for old rows without `eurocode_match`, the offer's primary eurocode code or "Brak eurocodeu". Links to the offer unless its status is "Usunięta". "Sku" shows the SKU or "Oferta nie istnieje" (red).
- Evidence: SubmissionResource.php:44-48, :56-171, :180-188; ListSubmissions.php:45-50; Create/EditSubmission.php.
- Note: columns read `$record->original_data['without_eurocode']` without null guard (SubmissionResource.php:98,114); all 1213 prod rows have the key.

### Opening a submission marks it read
- Tag: confirmed · Impact: normal
- What happens: ViewSubmission::mount sets is_read=true with saveQuietly (no model events).
- Evidence: ViewSubmission.php:73-82. Prod shows 1182 unread, so admins rarely open them (or process offers via the offer screen).

### ViewSubmission display
- Tag: confirmed · Impact: normal
- What happens: Sections "informacje" (Rodzaj zgłoszenia = title, Użytkownik, Oferta link, Data zgłoszenia; red banner "Uwaga! Oferta powiązana z tym zgłoszeniem nie istnieje lub została usunięta" if offer missing), warning "Uwaga! Dla tej oferty istnieje nowsze zgłoszenie. Podane tutaj informacje moga sie różnić od rzeczywistych." when a later submission exists for the same offer, "Powiązane elementy" (only when `eurocode_match` is set: typed code, "Przypisano do istniejącego? " icon, related offer SKU, colour name, glass type name when matched), "Dane wprowadzone przez użytkownika" (Wprowadzony Kod, Cena, Informacja od użytkownika, Opis, Ilość zdjęć w galerii, Status szyby, Ocena szyby, Ilość). Header: "Powrót" (browser back), "Usuń zgłoszenie" (DeleteAction with confirmation). Relation manager: OffersRelationManager (shared). The data shown is a snapshot, not live offer state. Car/resource sections (handleCarSection, handleResourceSection etc.) are dead private methods, never called.
- Evidence: ViewSubmission.php:55-70, :91-142, :144-217, :219-258, :260-295; dead code :297-441.
- Defect risk: line 174 `visible(fn($record)=>$record->original_data['eurocode_match']['matched'])` is only evaluated inside the section that is visible if `eurocode_match` is set, OK.

### Admin action "Dodaj eurokod" (Dodaj / przypisz eurokod)
- Tag: confirmed · Impact: high
- What happens: modal with form from EurocodeResource::getActionFormSchema: "Eurokod" (required, unique in eurocodes.code), "Kody OEM" (tags), "Kompatybilne pojazdy" (multi-select of cars, search by name). Prefilled with the user's typed code. On submit: code normalised (upper, spaces removed); `Eurocode::firstOrCreate(code, oem_codes)` (uuid primary key auto-generated); if cars selected -> `cars()->syncWithoutDetaching` (adds car compatibility to the eurocode, vendor Car/Eurocode pivot `car_eurocode`); notification "Eurokod utworzony" (shown always, even if firstOrCreate found existing); then, if the offer exists, `$offer->eurocodes()->sync($eurocode)` — the offer's eurocodes are replaced by this single eurocode (all other attached eurocodes, incl. alternates, are detached; pivot priority is not set so takes DB default); notification "Eurokod przypisany do oferty". Shown regardless of matched=true/false, whenever `eurocode_match` is in the data. No Offer model save occurs, so no `updated` hook / Woo / Otomoto sync is triggered by it; the submission is not modified or marked resolved.
- Evidence: ViewSubmission.php:176-211 (esp. :192-209, :205 sync); Filament/Clusters/Offers/Offers/Resources/EurocodeResource.php:57-89; Models/Eurocode.php:34-41.
- Defects?: the form's `unique` rule means an already existing code cannot be submitted, so firstOrCreate's "existing" branch is only reachable through race or case; an input that was previously matched (matched=true) can't be re-assigned via this action. `sync` wipes alt eurocodes. If offer is missing, notification still says assigned (second notification is outside the `if`). Tagged defect?.

### Admin action "Rozwiąż zgłoszenie" (resolve)
- Tag: confirmed · Impact: high (state + notifications + external sync)
- What happens: confirmation modal "Rozwiązywanie zgłoszenia" / "Ustaw nowy status oferty". Fields: `new_status` (required; options `Aktywna` / `Odrzucona`, labels are the raw enum values), "Informacja dla użytkownika" (optional textarea, placeholder "Pozostaw puste, aby wysłać bez dodatkowej treści"), checkbox "Usuń zgłoszenie po rozwiązaniu" (default checked). Button is disabled when the offer does not exist or a newer submission for the same offer exists. On submit:
  1. Aktywna -> OfferAcceptedAction; Odrzucona -> OfferRejectedAction, each given the optional text.
  2. If checkbox -> the submission is hard-deleted (model has no SoftDeletes). Otherwise it stays (still unread/read as is, no "resolved" marker; can be resolved again).
  3. After: page reloads via JS (window.location.reload()); if the submission was deleted the reload hits a deleted record (likely 404; unverified at runtime).
- No state guard: any offer status (Sold, Archived, Deleted, Reserved...) can be forced to Aktywna/Odrzucona. No transaction wraps steps.
- Evidence: ViewSubmission.php:443-505 (esp. :456 disabled, :459-472 form, :477-494 action, :496 after); no SoftDeletes in Models/Submission.php.

### Processing flow for resolve (end to end)
- Tag: confirmed (statically traced) · Impact: high
- Steps, in order:
  1. JournalAction::__invoke calls processAction: OfferAcceptedAction sets `offer.status = "Aktywna"` and `save()`; OfferRejectedAction sets `"Odrzucona"` and `save()`. (Journal/OfferAcceptedAction.php:20-26, OfferRejectedAction.php:52-58). Eurocodes, attributes, price, equipment are NOT touched; the submission's snapshot data is never applied to the offer (the offer already contains the user's data from the moment of save).
  2. Offer `updated` hook (Models/Offer.php:158-161) fires (only when something changed): (a) `OfferUpdatedEvent` -> OfferEventServiceProvider (registered bootstrap/providers.php:10) -> `UpdateAdvertAfterOfferChange` (queued, database queue): if the offer has an Otomoto record and status is Aktywna, `UpdateCoreAdvertData` pushes to Otomoto API for each advert (external; not traced). Rejected -> no Otomoto action. (b) `afterUpdateSyncAction`: if offer has a wooSync with auto_update and status in Active, Rejected, Reserved, Expired, Sold, Archived, Deleted -> `WoocommerceApiService::updateProduct` synchronous WooCommerce REST update (external; failure shows a Filament notification and is logged, does not abort). Pending is skipped.
  3. Owner gets a user journal entry: Accepted "Oferta została zaakceptowana!" / "Twoja oferta została zweryfikowana i zatwierdzona przez administratora. Nowy status oferty: Aktywna" + optional text, type Success; Rejected "Oferta została odrzucona" / "Niestety nie możemy opublikować Twojej oferty. Powód odrzucenia: " + optional text, type Rejection (empty reason leaves a dangling "Powód odrzucenia: "). Then a database notification (title = entry title, button "zobacz" to user journal list). Skipped silently when the offer has no user.
  4. Optional delete of the submission.
- Stop points: Eloquent save (vendor); WooCommerce client and Otomoto API (named, out of scope); queue worker runtime; Filament notification (vendor).
- Evidence: Abstracts/JournalAction.php:21-35, :37-54; Models/User.php:156-175 createJournalEntry (UserJournal::create); Models/Offer.php:156-165, :205-227; Services/Offer/OfferEventServiceProvider.php:19-34; Services/OtomotoService/Listeners/UpdateAdvertAfterOfferChange.php:27-39; Services/…/WoocommerceApiService.php:132-158.
- Note: the same Accept/Reject actions are reachable from the offer's AdminTab and AcceptOffersBulkAction; the submission screen is just another door to the same status change.

### Alternative entry points to the same status change
- Tag: confirmed · Impact: normal
- AdminTab (view offer): for Pending offers "Odrzuć ofertę" (required "Powód odrzucenia") / "Zatwierdź ofertę"; Active -> only reject; Rejected -> only accept (these are state-guarded, unlike submission resolve). Bulk accept: AcceptOffersBulkAction. Neither touches submissions, so a submission stays unread/unresolved after the offer is approved elsewhere.
- Evidence: AdminTab.php:100-170; AcceptOffersBulkAction.php:89.
- Defect?: AdminTab.php:127 for Pending reject has `->after(fn($livewire) => dd($livewire))` (debug dump) — outside scope, noted.

### Submission lifecycle with the offer
- Tag: confirmed · Impact: high (deletion)
- What happens: migration FK offer_id is NOT NULL with `onDelete('cascade')`; user_id nullable `set null`. But Offer uses SoftDeletes with a `deleted` hook setting status "Usunięta", so normal offer deletion does not cascade: submissions of deleted offers remain (200 of 1213 in prod point to offers with status "Usunięta"/deleted_at set) and the UI shows "Oferta nie istnieje". Cascade would apply only on force delete/DB-level delete. is_read added later (default false).
- Evidence: database/migrations/2024_11_10_145443_create_submissions_table.php:17-21; 2025_09_06_55555_add_read_status_to_submission.php:15-17; Models/Offer.php:52,163-176. Prod: offers by status for submissions: Aktywna 918, Usunięta 200, Odrzucona 8, Sprzedana 79, Archiwalna 7, Zarezerowana 1 (spelling as in data).

### Permissions
- Tag: ambiguous · Impact: high (permissions)
- What happens: SubmissionPolicy delegates to Shield permissions `*_submission`. In database/seeders/ShieldSeeder.php only "Super Admin" holds submission permissions; "Admin" and "User" have none. Actual prod role/permission data not read. The seeder may be stale relative to prod DB.
- Evidence: SubmissionPolicy.php:17-103; ShieldSeeder.php:15 (parsed JSON). Could be settled by an aggregate query on the prod role_has_permissions table.

### Duplicate "newer submission" logic
- Tag: confirmed · Impact: normal
- What happens: a submission is "outdated" if any later submission exists for the same offer; resolve is blocked on outdated ones. Prod: 187 of 1213 submissions have a newer sibling, so an admin must open/resolve (or delete) the newest first.
- Evidence: ViewSubmission.php:130-142, :456.

## Entry points
| Kind | Path | Does | Reachable |
|---|---|---|---|
| Action (create) | app/Filament/Actions/OfferCreatedByUserAction.php | creates Submission on user offer creation | yes, CreateOffer.php:94 |
| Action (create) | app/Filament/Actions/OfferEditedByUserAction.php | creates Submission on user edit | yes, EditOffer.php:118 |
| Action (create) | app/Filament/Actions/Journal/ActivateFromDraftAction.php | creates Submission on draft activation | yes, MyOffers.php:213 |
| Filament page | SubmissionResource index (ListSubmissions) | list, badge, mark read, bulk | yes |
| Filament page | ViewSubmission | view, resolve, add eurocode, delete | yes |
| Filament page | CreateSubmission / EditSubmission | empty-form CRUD | unreachable (routes commented out) |
| Table action | IconColumn set_as_read; bulk read_selected; DeleteBulkAction | read/delete | yes |
| Model hook | Offer::booted updated/deleted | Woo + Otomoto sync on save | yes |
| Listener | UpdateAdvertAfterOfferChange (queued) | Otomoto update | yes, provider registered |

## Contradictions and defects
- Archive scan claim "public-facing form" is wrong (see findings).
- Dead code in ViewSubmission (handleCarSection, handleResourceSection, handleResource, handleMultipleResource, getUrlAction).
- `OfferEditedByUserAction` and create path both run even for admin before the guard; create runs eurocode matching for admins too.
- Resolve without state guard and without transaction; stale "outdated" snapshot; JS reload after delete.
- `message` column unused; `createJournalEntry` called with an ignored 5th argument.

## Leads outside scope
- Offer `afterUpdateSyncAction` / Woo `updateProductToWordpressAction` and Otomoto `UpdateCoreAdvertData` internals.
- AdminTab.php:127 `dd($livewire)` debug code.
- ShieldSeeder Admin role lacks submission permissions; prod permission data not checked.
- UserJournal entries and notifications received by owners (r03 journal).
- Draft flow unused in prod for submissions (0 "ze szkicu").

## Could not establish
- Whether the page after "Usuń zgłoszenie po rozwiązaniu" reload 404s (needs runtime).
- Actual permissions of the Admin role in prod (aggregate query on permission tables would settle).
- Whether queued Otomoto listener runs in prod (queue worker config).

## Coverage ledger
- Searched: Submission, submissions, zgłoszen (app, routes, config, database, resources, tests, bootstrap), Submission::create, DB::table, OfferAcceptedAction/RejectedAction/EditedByUser/CreatedByUser/ActivateFromDraft callers, OfferUpdatedEvent/OfferDeletedEvent, SoftDeletes; git log -S "Submission::create"; prod aggregates (count, read, title, JSON keys, eurocode_match, offer status, newer siblings).
- Examined: Models/Submission.php, Policies/SubmissionPolicy.php, SubmissionResource.php + Create/Edit/List/ViewSubmission, both migrations, OfferCreatedByUserAction, OfferEditedByUserAction, ActivateFromDraftAction, OfferAccepted/RejectedAction, Abstracts/JournalAction, OfferEurocodeRelationService, Offer::booted, Eurocode::booted, EurocodeResource::getActionFormSchema, OfferEventServiceProvider, UpdateAdvertAfterOfferChange, WoocommerceApiService::updateProduct, CreateOffer/EditOffer hooks, MyOffers action, AdminTab, User::getAdmins/createJournalEntry, ShieldSeeder.
- Matched, not examined: other Journal/* actions (only unused `use Submission`), OfferInquiryResource Accept/Reject (grep hit on "zgłoszen" wording, r03), ShieldSeeder_old2, UpdateCoreAdvertData and Woo action internals (external, out of scope).
