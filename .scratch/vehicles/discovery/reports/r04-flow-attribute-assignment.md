# r04 — flow: how glass attributes get onto an offer

Question: When an offer is created or edited in V1, how do eurocode, alt eurocodes, OEM, VIN (`vim`), year, cars, colour, producer, glass type, equipment and extras get onto it — and what is derived from what? · V1 commit: 511ce3b

All paths are under /home/kp/laravel/projekty/AllGlass/app/ unless stated. "Forms dir" = Filament/Clusters/Offers/Resources/OfferResource.

## Summary
- Only two forms are live: OfferCreateForm (CreateOffer page, admin and seller) and OfferEditForm (EditOffer page). UserOfferCreateForm (the wizard with eurocode_alt, oem, vim, cars) is referenced nowhere, so it is unreachable.
- No live form writes `alt_eurocodes`, `oem`, `vim` or the `car_offer` pivot. They are written only by the Woo import (`alt_eurocodes`, cars) and an artisan command (`oem`). Alt eurocodes and OEM shown to the outside are derived from the eurocode pivot.
- The authoritative link is the `eurocode_offer` pivot, which has `priority` (0, 10, 20…). The first one is the primary. Admin picks eurocodes from the dictionary. A seller types free text into `offer.eurocode`, and after save the text is matched to the `eurocodes` table (by code or OEM) and the pivot is written. Nothing ever creates a Eurocode row from an offer form, except an admin's "create option" modal (EurocodeResource form).
- Colour, type and equipment are auto-filled from a "related offer" sharing the eurocode. Producer is never auto-filled. Cars come only through the eurocode (`car_eurocode`); the offer's own `car_offer` pivot is not set by any form.
- Eurocode change or removal: admin edit syncs the pivot exactly (old links removed). Seller edit replaces or detaches the pivot depending on the text. Colour, type and equipment copied earlier are never reverted.

## Findings

### Who sees what: the live create form (admin and seller share one form)
- Tag: confirmed · Impact: normal
- What happens: CreateOffer uses OfferResource::form -> OfferCreateForm. Fields and visibility:
  - Toggle `without_eurocode` ("Nie posiadam eurocodu / kodu OEM"), live. Everyone, create and edit.
  - Select `eurocodes` ("Eurocody (Admin)"): admin only. Hidden when `without_eurocode` is on.
  - TextInput `eurocode` ("Eurocode / kod OEM"): non-admin only. Hidden when `without_eurocode` is on. Helper text "Wpisz identyfikator szyby". `visible(!isAdmin)` and `hidden(...)` are combined, so an admin never sees it.
  - Textarea `user_description` ("Informacje o pojeździe"): shown only when `without_eurocode` is on.
  - `year` ("Rok produkcji", placeholder YYYY): shown if usage_status is "Używana" or "Outlet", or the user is an admin.
  - `offer_type_id`, `offer_color_id`: shown to admin always; to a seller only if `without_eurocode` is on.
  - `offer_producer_id`: always shown.
  - `equipment` ("Dodatkowe wyposażenie"): admin only, in the relation grid.
  - `shipping_method_id`: admin only (out of scope).
- Evidence: Forms dir/Components/OfferCreateForm.php:182-232 (toggle, eurocode, user_description); :360-365 year; :368-430 type, colour, producer; :438-469 admin-only grid with equipment. OfferEditForm.php:168-266 and :355-460 are the same widgets. CreateOffer, EditOffer and OfferResource.php:71 wire them.
- `oem`, `vim`, `alt_eurocodes` and cars: no input exists in either live form (grep of both files).

### Eurocode (admin): dictionary multi-select, ordered pivot
- Tag: confirmed · Impact: high (state)
- What happens: the admin searches `eurocodes.code` with `LIKE %term%` (limit 20) and picks several. Order of selection becomes priority 0, 10, 20…, and the first is the primary. On save `saveRelationshipsUsing` runs `$record->eurocodes()->sync($payload)`. This replaces the whole set, so removed eurocodes are detached. An admin with the right permission can create a new Eurocode through the "create option" modal (`EurocodeResource::getFormSchema`), so that is where a Eurocode row can be created. No required or exists validation on this field. The `offers.eurocode` string column is not written for admins, because their text field is hidden.
- Evidence: Forms dir/Components/Partials/SelectEurocodesPartial.php:28-99 (sync with priority, createOption for admin only).

### Eurocode (seller): free text with datalist, matched after save
- Tag: confirmed · Impact: high
- What happens: the text is stored in `offers.eurocode` as typed (no validation, no uppercase or space stripping on the column). While typing, a datalist suggests up to 25 `eurocodes.code` values that start with the normalised input (≥3 chars). It also matches `oem_codes` through `JSON_SEARCH` ... `typed%`, but the suggestion list returns the eurocode `code`, not the OEM. After the save, `OfferCreatedByUserAction::verifyRecordData` runs this logic:
  - Skip if `without_eurocode`.
  - Skip if the pivot is already non-empty.
  - Otherwise `findEurocodeFromUserInput`: uppercase, strip all whitespace, `code = X OR JSON_CONTAINS(oem_codes, X)`, first row. If matched, `eurocodes()->sync($matched->id)` (priority default 0). If not, nothing is linked.
  - The match result goes into `eurocode_match` in the submission payload only.
  - So a seller can enter an OEM number in the Eurocode field and get the Eurocode linked (OEM to eurocode resolution). VIN does not take part in any resolution.
- Evidence: Forms dir/Components/OfferCreateForm.php:191-222; Filament/Actions/OfferCreatedByUserAction.php:59-97; Services/Offer/OfferEurocodeRelationService.php:14-27, :81-84.

### "Without eurocode" path
- Tag: confirmed · Impact: high
- What happens: with the toggle on, `eurocodes` and `eurocode` are hidden and `user_description` appears. A seller then also sees type and colour and picks them by hand. On create, no pivot is written (verifyRecordData returns early). On seller edit with the toggle on, `OfferEditedByUserAction::verifyEurocodeData` sets `offers.eurocode = null` and detaches all `eurocode_offer` rows. On admin edit with the toggle on, nothing detaches the pivot (the admin Select is hidden and is not saved, `verifyEurocodeData` is not reached for admins), so old pivot rows may remain. This is `inferred` from Filament skipping hidden components; no runtime check. `areEurocodesEqual` returns false when the toggle is on, which makes `handleRelationsByEurocode` run, but that exits if `$data['eurocode']` is empty.
- Evidence: EditOffer.php:273-278; OfferEditedByUserAction.php:244-274; OfferCreatedByUserAction.php:62-64; Offer.php:65 (title "brak eurocodu").

### Auto-fill of colour, type and equipment from a related offer
- Tag: confirmed · Impact: normal
- There are four places. Colour, type and equipment are all copied, and always "only if empty" except the admin UI:
  1. Admin form, live: on any change to the `eurocodes` Select, the first selected eurocode finds the best related offer (`findBestRelatedOfferByEurocodeId`). Related means: status ≠ Pending, trashed included, has that eurocode, and is the latest by `created_at`, with the count of filled colour, type, producer and equipment as the tiebreak. It then overwrites form fields `offer_color_id`, `offer_type_id` and `equipment` (the last only when the related offer has equipment). The `equipment` helper text says "Uwaga! Pozostaw to pole puste, jeżeli chcesz, aby zostało automatycznie wypełnione na podstawie eurocode". Producer, year and cars are not copied. The create form writes `equipment` into the form; `beforeCreate` / `afterCreate` do not clear it.
  2. After create (any user): `OfferCreatedByUserAction::handleRelationsByEurocode` (saves quietly if no id). `findRelatedOfferByEurocodes` takes any other offer (status ≠ Pending, trashed included, latest) that shares ANY of this offer's eurocodes. It copies the missing alt eurocodes into the pivot (`syncWithoutDetaching`, next priority = max+10). It copies colour and type if empty, copies equipment if the offer has none, then `saveQuietly`. It runs for admin and seller, including drafts. It records `connected_attributes` into the submission payload.
  3. EditOffer `handleRelationsByEurocode` (runs before `record->update` when the eurocode set differs): finds another offer by the string columns `eurocode IN (...)` or `alt_eurocodes` JSON contains (legacy string columns, not the pivot). It fills empty colour and type and, if the offer has no equipment, `equipment()->sync`.
  4. Seller edit, `OfferEditedByUserAction::handleRelationsByEurocode`: same as 2 but only after a newly matched eurocode.
- Evidence: SelectEurocodesPartial.php:47-76; OfferEurocodeRelationService.php:37-74; OfferCreatedByUserAction.php:99-167; EditOffer.php:167-180, :224-271; OfferEditedByUserAction.php:368-434.
- Note: the `$record->offer_color_id =` assignments in EditOffer happen before `$record->update($data)`, so form data wins over them.

### Alternative eurocodes
- Tag: confirmed · Impact: normal
- What happens: there is no input for `alt_eurocodes` in live forms. "Alt" means pivot rows 2…n by priority (`getAltEurocodes`). The `alt_eurocodes` JSON column is legacy: written by the Woo import, versioned and read by EditOffer's lookup (reads `$data['alt_eurocodes']`, which no live form posts, so practically only the `eurocode` string is used). Alt rows get onto the pivot by an admin picking several or by related-offer propagation (finding 2). The same propagation also pulls in an offer's alts when a seller enters a code already known on another offer.
- Evidence: Offer.php:438-455; Filament/Actions/OfferCreatedByUserAction.php:120-144; Services/ApiService/Jobs/FetchWooProducts.php:117.

### OEM
- Tag: confirmed · Impact: normal
- What happens: `offers.oem` is never filled by the offer forms. OEM is a property of the Eurocode (`eurocodes.oem_codes` JSON). `Offer::getAllOem` and `getPrimaryOem` are derived from the pivot, in priority order. `getPrimaryOem` is the first OEM of the primary eurocode, and it feeds the Woo ACF field `oem`. The `offers.oem` column is filled only by the artisan command GetOemsFromWordpressOffers (a lead). The seller can enter an OEM into the eurocode text field and it is resolved to a Eurocode (see above).
- Evidence: Offer.php:457-477; Services/ApiService/DTO/WooProductDTO.php:78; Console/Commands/GetOemsFromWordpressOffers.php:17.

### VIN (`vim`)
- Tag: confirmed · Impact: normal
- What happens: `offers.vim` (string, nullable) exists in the migration. Only the unreachable UserOfferCreateForm has a "Vim" input (maxLength 255, no required). No live form, no resolver and no import reads or writes it, and it is commented out of the activity-log fields. Nothing derives anything from VIN or into VIN.
- Evidence: database/migrations/2024_10_06_151733_create_offers_table.php:35; Forms dir/Components/UserOfferCreateForm.php:158-161; Offer.php:236-237 (commented). The grep `vim` over app, routes and resources found only these.

### Year
- Tag: confirmed · Impact: normal
- What happens: free text `year` (string column, placeholder "YYYY"), no validation (no numeric, min or max rule). It is hidden from a seller unless the glass is "Używana" or "Outlet"; an admin always sees it. A seller who creates a new ("Nowa") glass cannot set it. It is versioned and logged. It is excluded when an offer is duplicated (replicate) and the edit sets nothing for it. The Woo import sets it from ACF.
- Evidence: OfferCreateForm.php:360-365; OfferEditForm.php:355-360; Offer.php:134, :240, :610-620 (replicate excludes year, producer, sku, status, notes).

### Glass type, colour, producer: dictionary selects
- Tag: confirmed · Impact: normal
- What happens: they are FK selects (`offer_type_id`, `offer_color_id`, `offer_producer_id`) on `type`, `color` and `producer`. No `required`.
  - Type options: Active only, ordered by offer count. For a seller they are further limited to the names PRZEDNIA, TYLNA, BOCZNA, SZYBA DACHOWA. On edit, rows with a `tmp_session` equal to the current session are also offered.
  - Colour: Active only, ordered by offer count.
  - Producer: on create, Active or the session's temporary rows; on edit, any status except Pending.
  - Producer has a "create option" on create (name form with a `status` select shown to admin only; a seller's new producer defaults to Pending). `CreateOffer::beforeCreate` clears `tmp_session` on the chosen producer and deletes other producers created in this session. The type and colour classes are listed in EditOffer::beforeCreate, which never runs on edit (dead hook).
  - Only the wizard (unreachable) could create type, colour and equipment from the offer form.
- Evidence: OfferCreateForm.php:368-430; OfferEditForm.php:363-425; CreateOffer.php:46-81; EditOffer.php:183-216.

### Equipment ("Dodatkowe wyposażenie")
- Tag: confirmed · Impact: normal
- What happens: this is an admin-only multi-select (searchable, preload) on Active equipment or the session's temporary rows, saved to the `offer_offer_equipment` pivot by Filament's relationship save (no custom code). A seller cannot set equipment in live forms; it only arrives through the related-offer propagation (see above), or the Woo import. In `CreateOffer::beforeCreate`, selected equipment loses its `tmp_session`, and other equipment rows made in the session but not selected are deleted. This `tmp_session` code is also only effective where the "create option" exists (admin create form has none for equipment, so effectively dead there).
- Evidence: OfferCreateForm.php:457-467; OfferEditForm.php:449-458; CreateOffer.php:69-79.

### Cars
- Tag: confirmed · Impact: normal
- What happens: no live form assigns cars. A car relation reaches an offer only through its eurocodes: `car_eurocode` joined with `eurocode_offer` (`eurocodeCarsQuery` / `getEurocodeCars`). An admin assigns cars on the Eurocode record (`EurocodeResource` has a `cars` select). The direct `car_offer` pivot (`Offer::cars()`) is written only by the Woo import (`$offer->cars()->attach`) and read by the `GenerateEurocodesAndRelationFromOffers` command, which copies offer cars into the eurocode. Woo sync is blocked ("Oferta nie posiada powiazanego pojazdu") when `getEurocodeCars()` is empty, so cars derived from the eurocode gate publishing.
- Evidence: Offer.php:263-265, :479-496, :538; Services/ApiService/Jobs/FetchWooProducts.php:273; Console/Commands/GenerateEurocodesAndRelationFromOffers.php:122-126; Filament/Clusters/Offers/Offers/Resources/EurocodeResource.php:44-48.

### Admin edit: what changes when eurocodes are changed
- Tag: confirmed · Impact: high
- What happens, in order (`EditOffer`):
  1. `mutateFormDataBeforeSave`: draft toggle and status. A seller save forces status Pending, unless saved as draft (out of scope in detail).
  2. `handleRecordUpdate`: `areEurocodesEqual` compares the pivot IDs to the form's `eurocodes`. When the toggle is on it returns "not equal". If not equal, `handleRelationsByEurocode` runs (see 3 above).
  3. `$record->update($data)` fires the `updated` model event, then Filament saves relations: `eurocodes` sync (exact), `equipment` sync from the form.
  4. `afterSave` runs `OfferEditedByUserAction`: `verifyRecordData` forces rating to 5 for a "Nowa" glass and `saveQuietly`. For an admin it stops there.
- For a seller edit, `eurocodes` is not in the form data, because the Select is invisible. So `areEurocodesEqual` compares old IDs to `[]`: equal only when no eurocode is linked yet. `verifyEurocodeData` then runs (see below), and then a Submission is always created.
- Seller changes the text:
  - Empty text: all pivot rows are detached.
  - Text not matched: all pivot rows are detached, and `offers.eurocode` still holds the text.
  - Matched and already among the linked codes: no change. Alt links stay.
  - Matched to something new: `sync($matched->id)` removes ALL other pivot rows including alts, then related-offer propagation fills the alts back from another offer using it.
  - Colour, type and equipment copied earlier are not removed or recomputed.
- A deleted offer: the pivot is not touched; FK cascade on offer hard-delete (see below).
- Evidence: EditOffer.php:152-180, :273-296; OfferEditedByUserAction.php:236-334.

### Model hooks and side effects when saving
- Tag: confirmed · Impact: high (external system)
- What happens: Offer::booted `updated` fires `OfferUpdatedEvent`, then `afterUpdateSyncAction`. Notes:
  - It fires on every non-quiet update. `saveQuietly` and the pivot syncs do not fire it. Create does not fire `updated`.
  - `OfferUpdatedEvent` has one listener: Otomoto's `UpdateAdvertAfterOfferChange` (queued; acts only if the offer has an Otomoto advert and is Active). Not followed (Otomoto dropped per roadmap).
  - `afterUpdateSyncAction` calls `WoocommerceApiService::updateProduct($offer)` if `wooSync` exists with `auto_update`, status is not Pending, and status is in the allowed list. The sync payload includes the eurocode, OEM, alt eurocodes and others (`fillACFFromOffer`) read from the pivot. Because the pivot sync happens after `update()` and pivot or `saveQuietly` changes fire nothing, the Woo push in this save may carry the previous pivot state. This is `inferred` from the order; not run.
  - Other: `versionable` (price, eurocode, year, alt_eurocodes, without_eurocode…) makes revisions. The activity log tracks eurocode and year, not oem, vim, alt_eurocodes. No observer was found for the pivot.
  - No DB transaction wraps any of this (handleRecordUpdate, the actions and the Filament relation save are separate writes).
- Evidence: Offer.php:156-161, :201-227, :122-135, :229-250; Services/Offer/OfferEventServiceProvider.php:104-119; Services/OtomotoService/Listeners/UpdateAdvertAfterOfferChange.php:28-42; bootstrap/providers.php registers OfferEventServiceProvider.

### Create flow, whole sequence
- Tag: confirmed · Impact: high
- What happens: `mutateFormDataBeforeCreate` sets the status (draft toggle -> Draft; a non-admin -> Pending; admin otherwise whatever the status select says). `beforeCreate` cleans temp producer or equipment. The row is inserted (eurocode text, `without_eurocode`, `year`, FKs, `user_description`). Filament saves the relations (admin: eurocodes sync, equipment sync). Then `afterCreate`: manual SKU; `OfferCreatedByUserAction` (verify and match, propagation, journal action, and for a seller a Submission titled "Użytkownik utworzył ofertę" plus a database notification "Uzytkownik dodał nową ofertę!" to every admin). `original_data` of the Submission carries `eurocode_match` and `connected_attributes`.
- Evidence: CreateOffer.php:34-95; OfferCreatedByUserAction.php:33-57.

### FK cascades
- Tag: confirmed · Impact: high
- `eurocode_offer.offer_id` and `eurocode_id` both cascade on delete. `car_eurocode` cascades on both sides.
- Evidence: database/migrations/2025_08_30_131935_create_eurocodes_table.php:23-37.

## Entry points
| Kind | Path | Does | Reachable |
|---|---|---|---|
| Filament page | Pages/CreateOffer.php | Create (admin and seller), full chain above | yes |
| Filament page | Pages/EditOffer.php | Edit, `handleRecordUpdate`, `afterSave` | yes |
| Form | Components/OfferCreateForm.php / OfferEditForm.php | Widgets | yes |
| Form | Components/UserOfferCreateForm.php | Wizard with `eurocode_alt`, `oem`, `vim`, car steps | unreachable (no reference in the whole of app/) |
| Partial | Partials/SelectEurocodesPartial.php | Admin eurocode picker plus auto-fill. `searchByOemAction` and `editEurocodeHintAction` are private and uncalled (the call is commented out) | partly |
| Model event | Offer::booted updated | Event, Woo update | yes |
| Table action | Offer::getReplicateTableAction | Duplicates an offer: copies equipment and eurocode pivot with priority; excludes year, producer, sku | yes |
| Service | OfferEurocodeRelationService::assignRelationsByEurocode | Empty body | unreachable stub |

## Contradictions and defects
- defect? `UserOfferCreateForm` uses `relationship('car', ...)` on `car_id`, but Offer has `cars()` (many to many) and no `car` relation. It is also unreachable.
- defect? Seller edit with a new matched eurocode `sync($matched->id)` drops the alt eurocodes first. Propagation may restore them, but only if another offer has them.
- defect? EditOffer::beforeCreate is dead on edit. EditOffer reads `$data['alt_eurocodes']`, which no form posts. `eurocode_alt` (wizard) does not match any column.
- defect? Admin edit with `without_eurocode` on does not clear the pivot (the seller path does). Inferred.
- decide: whether V2 keeps the seller free-text eurocode with after-the-fact matching (and silent no-match), and whether OEM-in-eurocode-field resolution is wanted.
- decide: legacy columns `oem`, `vim`, `alt_eurocodes`, `eurocode` (string): keep, or derive from the pivot only.

## Leads outside scope
- Services/ApiService/Jobs/FetchWooProducts.php:117-290: Woo import writes `eurocode`, `alt_eurocodes`, `year`, cars, equipment, producer.
- Console/Commands/GenerateEurocodesAndRelationFromOffers.php: builds Eurocode rows from offers' `eurocode`, `alt_eurocodes` and cars. This is the only place that creates Eurocode rows from offers.
- Console/Commands/GetOemsFromWordpressOffers.php: fills `offers.oem`.
- Filament/Resources/OtomotoOfferResource/Components/OtomotoSynchronizationTab.php:145-160: another cars select.
- Filament/Clusters/Offers/Offers/Resources/EurocodeResource.php: form with cars and oem_codes (Eurocode CRUD).
- Filament/Actions/Journal/OfferCreatedAction, DraftOfferCreatedAction: journal.

## Could not establish
- Whether hidden Filament fields are dehydrated (admin edit with the toggle on): based on my knowledge of Filament 3; runtime would settle it.
- Whether the Woo push in the same save sees the new pivot: ordering reasoned only.
- Which Filament form the seller sees at "MyOffers" (not looked at beyond grep for forms): grep for `OfferCreateForm` found only OfferResource, so I assume sellers use the same resource. Not checked against routes or panels.

## Coverage ledger
- Searched: UserOfferCreateForm, OfferCreateForm and OfferEditForm references; `vim`; `alt_eurocodes`; `'oem'`; `cars()`; `eurocode_offer`; `afterUpdateSyncAction`; `without_eurocode`.
- Examined: OfferCreateForm.php (all), OfferEditForm.php (30-497), UserOfferCreateForm.php (95-400, grep for the rest), CreateOffer.php, EditOffer.php, SelectEurocodesPartial.php, OfferEurocodeRelationService.php, OfferEventServiceProvider.php, OfferUpdateService.php, OfferCreatedByUserAction.php, OfferEditedByUserAction.php, Offer.php (hooks, relations, replicate, versionable), UpdateAdvertAfterOfferChange.php, OfferUpdatedEvent.php, migrations create_offers_table and create_eurocodes_table.
- Matched, not examined: Woo import and DTO (outside scope), the Eurocode and Car models and EurocodeResource (Eurocode CRUD, other mission), WoocommerceApiService internals, Otomoto UpdateCoreAdvertData, Journal actions, the rest of OfferResource (table), the seller "MyOffers" page.
