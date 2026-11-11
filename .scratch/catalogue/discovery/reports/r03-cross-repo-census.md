# r03 — cross-repo: where else Vehicles reference data lives, and entry-point census

Question: Where else in V1 does this reference data live or get read/written, and what is the full entry-point census? · V1 commit: 511ce3b

## Summary
- Vehicles data is used mainly through the Offer: the Offer form, Offer tables and filters, Offer view, the Submission review page, Woo/WordPress sync and Otomoto advert creation. Few of those files are the anchors.
- There are two parallel offer->car paths. Current code reads `offer -> eurocode_offer -> eurocodes -> car_eurocode -> cars` (`Offer::eurocodeCarsQuery`). The legacy `car_offer` pivot (`Offer::cars()`) is written only by the WordPress product import (`FetchWooProducts::handleCars`) and read nowhere else I found. Two legacy offer columns remain: the plain `offers.eurocode` string and `offers.oem`/`vim`/`alt_eurocodes`.
- Non-admin users type the eurocode as free text. After save it is matched to a `Eurocode` (`OfferCreatedByUserAction`, `OfferEditedByUserAction`), and the offer's colour, type and equipment are copied from a related offer. Admins pick `Eurocode` rows directly.
- The Donor* (Otomoto) catalogue has no FK or link to Car/Eurocode. It is matched to `Car` at advert-creation time by name/brand/model/year string matching (`OtomotoDonorCarMatchService`). Nothing flows back.
- WordPress export: Car -> CPT `vehicles` (`vehicle` in local env), OfferEquipment -> `glass-accessories`, OfferProducer -> `glass-producents`, OfferColor -> `glass-colors`, OfferType -> Woo `products/categories`. Eurocode, OEM and alt eurocodes go out as ACF fields on the Woo product. The WordPress import creates Car, Equipment, Producer, Colour and Type rows from WordPress.
- FK cascades are few. `eurocode_offer` and `car_eurocode` cascade on delete, and `shipping_method_id` is `nullOnDelete`. All the other offer->taxonomy columns, `car_offer` and `offer_offer_equipment` have no DB FK, so deletes leave orphans (see Findings).
- No local V1 DB or SQL dump is available (see Could not establish). Row counts not given.
- Census: 46 rows below. Dead or unreachable: `UserOfferCreateForm`, `OfferExtras`, `OfferResourcesSeeder` (call commented out), `car_brands`/`CarBrand`.

## Findings

### Non-admin users enter a eurocode as free text; the system matches it to a Eurocode after save
- Tag: confirmed · Impact: high (state)
- What happens: non-admins get a text field "Eurocode / kod OEM" (datalist suggestions need ≥3 chars and search `eurocodes.code LIKE x%` OR `JSON_SEARCH(oem_codes)`), and a toggle "Nie posiadam eurocodu / kodu OEM". Admins instead get a multi-select "Eurocody (Admin)" over `Eurocode` (search `code LIKE %x%`, max 20). Selecting a eurocode as admin auto-fills colour, type and equipment from the "best related offer" (`OfferEurocodeRelationService::findBestRelatedOfferByEurocodeId`). Admins can create a Eurocode inline (`EurocodeResource::getFormSchema`). The pivot gets `priority` 0,10,20… in the selection order.
- After create/save, `OfferCreatedByUserAction` / `OfferEditedByUserAction` run. If `without_eurocode` is set, the edit action clears `offers.eurocode` and detaches all eurocodes. Otherwise it matches the typed string (`findEurocodeFromUserInput`), syncs the match, and records `eurocode_match {input, matched}` into the submission's `original_data`. When matched, it adds the related offer's missing alt eurocodes (`syncWithoutDetaching`) and copies colour, type and equipment only when the offer's own are empty. These show in the Submission review as `connected_attributes`.
- Evidence: `app/Filament/Clusters/Offers/Resources/OfferResource/Components/OfferCreateForm.php:182-230` (toggle, text input, datalist); `.../Partials/SelectEurocodesPartial.php:20-95` (admin select, autofill, saveRelationshipsUsing priority); `app/Filament/Actions/OfferCreatedByUserAction.php:42-165` and `OfferEditedByUserAction.php:42-225` (matching, `connected_attributes`); `OfferEditForm.php:168-264` (same fields on edit).

### Edit of an offer also copies colour/type/equipment from an offer with the same legacy eurocode string
- Tag: confirmed · Impact: normal
- What happens: `EditOffer::handleRecordUpdate` runs when the selected eurocodes changed (or `without_eurocode` is true, because `areEurocodesEqual` returns false). It finds another offer (including soft-deleted: `withTrashed`) whose `offers.eurocode` equals this offer's `eurocode` or alt_eurocodes, or whose `alt_eurocodes` JSON contains it. It copies colour and type when empty, and syncs equipment when the record has none. This uses the legacy string columns, not the `eurocodes` relation. It overlaps with the `OfferEditedByUserAction` logic.
- Evidence: `app/Filament/Clusters/Offers/Resources/OfferResource/Pages/EditOffer.php:63-195`.

### Offer form pickers for taxonomies, with role limits
- Tag: confirmed · Impact: normal
- What happens: "Rodzaj szyby" (type) and "Barwa szyby" (colour) are shown only to admins, or when `without_eurocode` is on. Non-admins can only pick type names `PRZEDNIA`, `TYLNA`, `BOCZNA`, `SZYBA DACHOWA`. Only Active rows are offered, ordered by `offers_count` desc. "Producent szyby" shows Active rows plus the user's own pending ones (`tmp_session = session id`), and users can create a producer inline (it starts as a temporary row with `tmp_session`). "Dodatkowe wyposażenie" and "Metoda wysyłki" (`shipping_method_id`) are admin-only (`getRelationFields` is visible only to admins).
- Evidence: `OfferCreateForm.php:368-471` (type, colour, producer, equipment pickers); `OfferEditForm.php:363-460`.

### Creating an offer promotes the chosen temporary taxonomy rows and deletes the session's other temporary rows
- Tag: confirmed · Impact: high (deletion of reference data)
- What happens: `CreateOffer::beforeCreate` sets `tmp_session=null` on the chosen producer and equipment rows (promotion). It then DELETES every `OfferProducer` with `tmp_session` = the current session id, and every `OfferEquipment` with this session id that is not in the selected list. `EditOffer::beforeCreate` has the same code, also for `OfferType` and `OfferColor`. Note: it is named `beforeCreate`, which `EditRecord` does not call (see Contradictions).
- Evidence: `app/Filament/Clusters/Offers/Resources/OfferResource/Pages/CreateOffer.php:46-82`; `.../EditOffer.php:78-115`; `database/migrations/2024_12_09_163344_add_tmp_session_for_offer_resources.php` (tmp_session on `offer_producers`, `offer_colors`, `offer_types`, `offer_equipment`).

### Submission review lets an admin create/assign a Eurocode and see user-created pending taxonomies
- Tag: confirmed · Impact: high (state)
- What happens: on the Submission view, the "Powiązane elementy" section (shown only if `original_data.eurocode_match` is set) has a "Dodaj eurokod" button, modal "Dodaj / przypisz eurokod". It does `Eurocode::firstOrCreate(code uppercased with spaces removed, oem_codes)`, then `syncWithoutDetaching` the chosen cars into `car_eurocode`, then `$offer->eurocodes()->sync($eurocode)`. This REPLACES all of the offer's eurocodes with this one. It sends two notifications: "Eurokod utworzony" and "Eurokod przypisany do oferty". The section "Specyfikacja szyby" shows type, producer, colour and equipment as "brak" (red), "Utworzył : <name>" (yellow, with an "Edytuj" link, when the row is `Pending`), or "Powiązany z: <name>" (green). The "Informacje o pojeździe" fieldset shows `car_id`/`car_brand`/`car_model`/`car_type`/`car_additional_info` from `original_data`.
- Evidence: `app/Filament/Resources/SubmissionResource/Pages/ViewSubmission.php:143-215, 296-345, 352-426`; `SubmissionResource.php:94-129` (table column of match result).

### Offer tables and filters: eurocode column, type/producer/colour filters, "Bez eurocodu" tab
- Tag: confirmed · Impact: normal
- What happens: `EurocodeTextColumn` ("Eurocody:") is a searchable badge list (`eurocodes.code`, 2 shown, expandable). When there are no relation rows its placeholder is the legacy `offers.eurocode`, or "---". It is used in 11 tables. The filters "Typ szyby", "producent" and "kolor" are `SelectFilter::relationship` on `type`, `producer` and `color`. In "MyOffers" they offer only values used by the user's own offers. FullListOffer has the tab "Bez eurocodu" (`without_eurocode = 1`, badge from `Offer::getWithoutEurocode`). Search placeholder in "MyOffers" is "eurocode, nr OEM lub SKU", but only `eurocodes.code` is searchable (no `oem` or `vim` column is searchable there). The note about OEM/VIM search in FullListOfferTable is commented out.
- Evidence: `.../Partials/EurocodeTextColumn.php:11-22`; `app/Filament/Pages/FullListOffer.php:109-112, 218-230, 376-380`; `app/Filament/Pages/MyOffers.php:63, 130-155`; `app/Livewire/FullListOfferTable.php:147-151, 272, 290`.

### Offer view shows eurocodes, priority reordering and cars; admin-only OEM
- Tag: confirmed · Impact: normal
- What happens: ViewOffer shows `eurocodes.code` with an admin-only suffix action "Ustal priorytety eurokodów" (drag-reorder repeater, writes pivot `priority` via `sync`, notification "Zapisano priorytety eurokodów"). It shows "(Admin) Kod OEM:" (`getPrimaryOem`), the legacy "Eurocode / Kod OEM:" for non-admins when no relation exists, `eurocode_cars` (from `getEurocodeCars`), "Dodatki" (equipment names), and type/producer/colour with links to their admin pages (admins only). Primary eurocode = lowest `priority` (`eurocodes()->first()`; ordering defined in `Offer::eurocodes`).
- Evidence: `ViewOffer.php:176-216, 290-325, 389-404`; `ReorderEurocodesViewAction.php:15-73`; `Offer.php:420-477`.

### Replicating an offer copies equipment and eurocode pivot with priorities; resets producer and year
- Tag: confirmed · Impact: normal
- What happens: the replicate table action excludes `offer_producer_id`, `sku`, `year`, `status` and others. It keeps type and colour and sets new `user_id`, `quantity=1`, `status=Pending`. After save it syncs equipment IDs and eurocodes with their priority.
- Evidence: `app/Models/Offer.php:606-645`.

### Offer rules that depend on the reference data (guards)
- Tag: confirmed · Impact: high (external systems)
- What happens: a Woo sync is blocked unless `primaryEurocode()->code`, price>0 and sku exist, and unless type, producer and colour are not `Pending` (the later part of `canBeWooSynch` was read only to line 330). An Otomoto advert is blocked unless status is Active/Reserved, at least one car is reachable via eurocodes ("Oferta nie posiada powiazanego pojazdu"), and type, producer and colour exist and are not `Pending` (messages "Typ szyby - brak", "Barwa szyby - oczekuje"). Offer loggable title is "brak eurocodu" when `without_eurocode`.
- Evidence: `app/Models/Offer.php:61-66, 259-290, 292-340`.

### Woo/WordPress export: which attribute goes where
- Tag: confirmed · Impact: high (external systems)
- What happens (record only): Woo product `name` = primary eurocode code, else sku. ACF fields: `eurocode` (primary code), `oem` (first OEM of the first eurocode via `getPrimaryOem`), `eurocode_alt` (alt eurocode codes joined by ", "). OfferType -> Woo category (`products/categories`, matched by slug, `wordpress_id` stored in `wordpress_syncs`) added to the product categories. Colour -> ACF `color` and Producer -> ACF `brand`; the value is the WP post id returned by `syncModel` (`glass-colors` / `glass-producents`). Cars of the offer's eurocodes -> ACF `vehicle` (array of WP post ids; endpoint `vehicles`, or `vehicle` when `APP_ENV=local`). Equipment -> ACF repeater `accessories[].accessory` (endpoint `glass-accessories`). Keyed by the `wordpress_syncs` morph row (`wordpress_id`, `syncable_type/id`). Shipping method is not exported.
- Evidence: `app/Services/ApiService/DTO/WooProductDTO.php:55-82`; `Actions/SyncProductToWordpressAction.php:51-53, 121-175` (and the `Update…` twin at 36-38, 100-155); `Actions/SyncProductCategoryAction.php:12-50`; `Models/Car.php:177-185`; `OfferEquipment.php:66-73`; `OfferProducer.php:70-81`; `OfferColor.php:69-76`.

### WordPress import creates reference rows and offers
- Tag: confirmed · Impact: high (state)
- What happens: ApiSettings page button "Pobierz Oferty z Woocommerce" clears 6 cache flags and starts `FetchWordpressDataOrchestrator`, which runs in order: cars, equipment, producers, colours, offer types (Woo categories, status Active), then products. Cars are created (name/slug/brand/model/year/type from ACF `brand`,`model`,`year`,`version`) and linked in `wordpress_syncs`. The product import sets `offers.eurocode`, `without_eurocode = !hasEurocode`, `alt_eurocodes` (comma list split), usage_status, rating; it attaches `car_offer` (legacy pivot), `offer_offer_equipment`, and producer/colour/type via `handleRelation`. It does NOT attach `eurocode_offer` rows (grep for `eurocodes()` in `FetchWooProducts` found none). The job creates rows with `Model::create` without a duplicate check (I read the Cars and OfferTypes chunk handlers only; Colors/Producers/Equipment have the same shape, not read in full).
- Evidence: `app/Filament/Clusters/Settings/Pages/ApiSettings.php:82-96`; `WoocommerceApiService.php:187-216`; `Jobs/FetchWordpressDataOrchestrator.php:28-62`; `Jobs/FetchWordpressCars.php:95-125`; `Jobs/FetchWooOfferTypes.php:94-120`; `Jobs/FetchWooProducts.php:94-135, 254-300`.

### Otomoto adverts are built from this data; Donor* is matched by name only
- Tag: confirmed · Impact: high (external systems)
- What happens: advert title = car brand/model/year/type; description lists producer, eurocode, alternatives, "Nr oryginału (OEM)" and equipment names ("Wyposażenie"). The car shown is one of the offer's eurocode cars (user may choose one in "select-cars"; the choice is also stored in `meta.selected_car_id`). `OtomotoDonorCarMatchService::matchCarToDonorData(Car)` finds the make by `LOWER(name|code) = car.brand`, then the keyword in the name; the model by exact `car.model` then LIKE keywords (shortest name wins); the generation by `car.year` then keywords. No FK or table links `cars` to `donor_*`, and nothing is written back to `cars`. The Otomoto param `Oem` uses `getPrimaryOem`; the producer param uses `producer.name`. Admins map OfferTypes to Otomoto categories and ShippingMethods to Otomoto shipping options on "OtomotoOptions" (stored in meta keys `otomoto_offer_type_mapping`, `otomoto_shipping_mapping`, not in these tables). The shipping method price is printed in the description unless the user has own `shipping_information`.
- Evidence: `OtomotoCreationService.php:41-235, 290-297, 143-156`; `OtomotoDonorCarMatchService.php:15-110`; `config/otomoto.php:38-49`; `OtomotoOptions.php:200-262, 330-345, 456-525`; `OtomotoSynchronizationTab.php:50-240`; `Pages/CreateOtomotoOffer.php:30-100`; `FetchDonorGenerations.php:66-100` (writes only `donor_cars`).

### Eurocode reference data written by legacy/one-off commands
- Tag: confirmed · Impact: high (state)
- What happens: `wordpress:sync-oems` takes offers with `oem IS NULL`, sku not like `AG%`, linked to Woo, and in chunks of 10 on queue `oem_codes` fills `offers.oem` (the legacy column) from the Woo product's ACF `oem`. It does not touch `eurocodes.oem_codes`. `oem:migrate-to-json` copies the legacy `eurocodes.oem_code` string into the `oem_codes` JSON array. `eurocodes:sync-models` is marked "[DEPRECATED]" (generates Eurocode rows and offer/car links from offers). Details are in r01/r02; recorded here for the census.
- Evidence: `app/Console/Commands/GetOemsFromWordpressOffers.php:12-27`; `app/Jobs/Offer/SynchOemCodeFromWordpressJob.php:45-62`; `MigrateOemCodesToJsonCommand.php:10-14`; `GenerateEurocodesAndRelationFromOffers.php:20-24`; `database/migrations/2026_03_28_000001_add_oem_codes_to_eurocodes_table.php:10-14`.

### FK cascades and missing FKs touching these tables
- Tag: confirmed · Impact: high (deletion)
- What happens:
  - `eurocode_offer.eurocode_id` -> eurocodes ON DELETE CASCADE; `eurocode_offer.offer_id` -> offers CASCADE. Deleting a Eurocode silently removes its offer links. Hard-deleting an offer removes its links.
  - `car_eurocode.car_id` -> cars CASCADE; `car_eurocode.eurocode_id` -> eurocodes CASCADE. Deleting a Car removes it from every eurocode.
  - `offers.shipping_method_id` and `offer_types.shipping_method_id` -> shipping_methods, `nullOnDelete`. Deleting a ShippingMethod clears it.
  - `donor_models.donor_make_id`, `donor_cars.donor_make_id`, `donor_cars.donor_model_id` cascade; `donor_generations.donor_model_id` cascades; `donor_cars.donor_generation_id` is set null. No link to Car.
  - NO DB FK (`foreignIdFor` without `constrained`): `offers.offer_producer_id`, `offer_color_id`, `offer_type_id`, `car_offer.*`, `offer_offer_equipment.*`. Deleting a producer/colour/type/equipment/car leaves orphans in these columns and pivots. The code relies on the Filament policies and `tmp_session`.
  - `car_brands` migration body is commented out, so the table is never created; `CarBrand` is imported by migrations and `Car.php` only.
- Evidence: `database/migrations/2025_08_30_131935_create_eurocodes_table.php:23-37`; `2025_08_17_55555_create_shipping_methods_table.php:23-25`; `2025_08_19_55555_add_shipping_to_glass_type.php:15-17`; `2025_05_30_191100_create_otomoto_cars_tables_setup.php:13-48`; `2024_10_06_151733_create_offers_table.php:46-48`; `2025_01_05_145235_car_offer_table.php:15-19`; `2024_10_20_145335_create_offer_offer_equipment.php:15-19`; `2024_10_06_144444_create_car_brands_table.php:13-19`.

### Offer columns that hold this data directly
- Tag: confirmed · Impact: normal
- What happens: `offers.eurocode` (string), `alt_eurocodes` (JSON array), `oem`, `vim`, `without_eurocode` (bool, default 0). `alt_eurocodes`, `eurocode`, `without_eurocode` are versioned (`$versionable`). Activity-log options track `eurocode`, `type.name`, `producer.name`, `color.name`. `oem`/`vim` columns are not shown on any current form; the only form that had them (`UserOfferCreateForm`) is unreferenced. `offer_extras` is a table with only `name`; model `OfferExtras` has no callers.
- Evidence: `database/migrations/2024_10_06_151733_create_offers_table.php:25,33-35,53`; `Offer.php:123-153, 225-247`; `UserOfferCreateForm.php:134-160`; `OfferExtras.php`; `database/migrations/2024_10_10_192433_create_offer_extras_table.php:13-18`.

### Reference-data authorisation is Shield permissions per resource
- Tag: confirmed · Impact: normal
- What happens: `CarPolicy` maps every ability to Spatie/Shield permissions (`view_any_car`, `create_car`, `delete_any_car`, `force_delete_car`, `replicate_car`, `reorder_car` …). The taxonomies have 2 policy copies each (`app/Policies/OfferColorPolicy.php` and `OfferPolicies/OfferColorPolicy.php`; same for producer, type); equipment has one. Which copy is bound, and whether Eurocode/ShippingMethod have policies: unknown (see Could not establish).
- Evidence: `app/Policies/CarPolicy.php:16-106`; `ls app/Policies app/Policies/OfferPolicies`.

## Entry points
| # | Kind | Path | Does | Reachable |
|---|---|---|---|---|
| 1 | Filament resource | app/Filament/Resources/CarResource.php (+Pages List/Create/Edit/View) | Admin CRUD "Baza pojazdów"; ListCars has slide-over create | reachable (registered; permissions per CarPolicy) |
| 2 | Relation manager | app/Filament/Resources/Shared/OffersRelationManager.php via ViewCar.php:48 | Lists offers of a car on the car view page | reachable (ViewCar) |
| 3 | Relation manager | app/Filament/Resources/CarResource/RelationManagers/OffersRelationManager.php | Same, Car-specific copy; its registration in `CarResource::getRelations` is commented out | unreachable via the resource (ViewCar uses the Shared one) |
| 4 | Filament resource | app/Filament/Clusters/Offers/Offers/Resources/EurocodeResource.php (+Pages) | Admin CRUD "Eurokody" | reachable |
| 5 | Filament resource | app/Filament/Clusters/Offers/Resources/OfferTaxonomies/OfferColorResource.php (+Pages) | "Kolory" CRUD + view | reachable |
| 6 | Filament resource | .../OfferTaxonomies/OfferProducerResource.php | "Producenci" CRUD + view | reachable |
| 7 | Filament resource | .../OfferTaxonomies/OfferTypeResource.php | "Rodzaje szyb" CRUD + view (has shipping method) | reachable |
| 8 | Filament resource | .../OfferTaxonomies/OfferEquipmentResource.php | "Dodatki / Wyposażenie" CRUD + view | reachable |
| 9 | Filament resource | app/Filament/Clusters/Offers/Offers/Resources/ShippingMethodResource.php | "Metody wysyłki" CRUD | reachable |
| 10 | Form + hook | OfferCreateForm.php / CreateOffer.php | New offer: eurocode text/admin select, type/colour/producer/equipment/shipping pickers; promotes and deletes temp rows; runs OfferCreatedByUserAction | reachable |
| 11 | Form + hook | OfferEditForm.php / EditOffer.php | Edit offer: same; `handleRecordUpdate` copies attributes from related offer; runs OfferEditedByUserAction | reachable (`EditOffer::beforeCreate` is not a hook there, see Contradictions) |
| 12 | Form partial | OfferResource/Components/Partials/SelectEurocodesPartial.php | Admin eurocode select with inline create; autofill via relation service; saves with priorities | reachable (used by 10, 11) |
| 13 | Form action | SelectEurocodesPartial::searchByOemAction ("Posiadam numer OEM") | Find eurocode by OEM via `whereJsonContains(oem_codes)` | unreachable (call is commented out at line 24) |
| 14 | Form action | SelectEurocodesPartial::editEurocodeHintAction ("Edytuj eurokod") | Edit selected eurocode in modal | unreachable (hintAction commented out; method private, never called) |
| 15 | Action class | app/Filament/Actions/OfferCreatedByUserAction.php | Matches typed eurocode, copies related attributes, writes submission data | reachable (CreateOffer::afterCreate) |
| 16 | Action class | app/Filament/Actions/OfferEditedByUserAction.php | Same on edit; clears eurocodes if `without_eurocode` | reachable (EditOffer::afterSave) |
| 17 | Service | app/Services/Offer/OfferEurocodeRelationService.php | Find eurocode from input; find related offer(s) | reachable (used by 12, 15, 16) |
| 18 | Form | OfferResource/Components/UserOfferCreateForm.php | Older user form with eurocode_alt, oem, vim, car brand/model/type cascade selects | unreachable (no references in app/ or routes/) |
| 19 | Page action | Partials/ReorderEurocodesViewAction.php ("Ustal priorytety eurokodów") | Admin reorders offer's eurocodes (pivot priority) | reachable (ViewOffer suffix action, admin only) |
| 20 | Infolist | OfferResource/Pages/ViewOffer.php | Shows eurocodes, OEM (admin), cars, equipment, type/producer/colour with admin links | reachable |
| 21 | Table column | Partials/EurocodeTextColumn.php | Searchable eurocode badges in 11 tables (OfferResource, FullListOffer page and Livewire table, MyOffersTableSchema, ReservedOffersTableSchema, AdminReservedOffersTable, LatestOffers widget, Shared and Car OffersRelationManagers) | reachable |
| 22 | Filters | OfferResource.php, FullListOffer.php, FullListOfferTable.php, MyOffers.php | "Typ szyby" / "producent" / "kolor" SelectFilters | reachable |
| 23 | Tab | FullListOffer.php:109 | "Bez eurocodu" tab | reachable |
| 24 | Livewire | app/Livewire/FullListOfferTable.php (`full-list-offer-table`) | Offer table with eurocode column and taxonomy filters | reachable (embedded by the blade view `.../full-list-offer-page.blade.php:3`) |
| 25 | Page action | SubmissionResource/Pages/ViewSubmission.php ("Dodaj eurokod") | Create/assign eurocode to offer, link cars | reachable |
| 26 | Table column | SubmissionResource.php:94-129 | Shows eurocode match result per submission | reachable |
| 27 | Table column | UserJournalResource.php:121 | Shows offer's primary eurocode in journal | reachable |
| 28 | Table column | OtomotoOfferResource.php:104-120 | Eurocode column with search by `offer.eurocodes` | reachable |
| 29 | Page | app/Filament/Pages/UsersOtomotoOffers.php | Shows `offer.eurocode` (legacy string) | reachable |
| 30 | Page | app/Filament/Resources/OtomotoOfferResource/Pages/OtomotoOptions.php | Maps OfferType -> Otomoto category, ShippingMethod -> Otomoto option (meta) | reachable |
| 31 | Page / tab | OtomotoSynchronizationTab.php, CreateOtomotoOffer.php | Pick a car of the offer's eurocodes; build advert | reachable |
| 32 | Bulk actions | OtomotoOfferResource/Components/Bulk{AdvertCreate,Update,Publish,Activate,RemoveFromWp}Action.php; Actions/BulkCreateAdvertsAction.php | Show eurocode in confirmation lists; create adverts using car/eurocode data | reachable |
| 33 | Bulk action | OfferResource/Components/WooSyncBulkAction.php | Queue Woo sync (exports eurocode/OEM/cars/colour/producer/type/equipment) in chunks of 2 | reachable |
| 34 | Bulk action | OfferResource/Components/AcceptOffersBulkAction.php | Accept offers; lists eurocode; sync dispatch commented out, runs `OfferAcceptedAction` | reachable |
| 35 | Queued job | Services/ApiService/Actions/BulkSynchProductToWooAction.php | Pushes products to Woo | reachable (from 33) |
| 36 | Model hook | Offer::booted (`updated`/`deleted`) -> `afterUpdateSyncAction`; DTO/Actions `Sync/UpdateProductToWordpressAction` | Auto-update of Woo product when `wooSync.auto_update` | reachable |
| 37 | Page button | Settings/Pages/ApiSettings.php "Pobierz Oferty z Woocommerce" | Starts WordPress import orchestrator | reachable |
| 38 | Queued jobs | Services/ApiService/Jobs/FetchWordpress{Cars,Colors,Equipment,Producers}.php, FetchWooOfferTypes.php, FetchWooProducts.php, FetchWordpressDataOrchestrator.php | Import cars, equipment, producers, colours, types, products | reachable (from 37) |
| 39 | Artisan | otomoto:donor-sync {categoryId} (FetchDonorDataCommand) | Fetch Donor makes -> models -> generations (queues makes, donor_models, generations) | reachable (manual only; `routes/console.php:20` says named queues remain manual) |
| 40 | Artisan | otomoto:donor-generation-sync | Re-fetch donor generations | reachable (manual) |
| 41 | Artisan | wordpress:sync-oems | Fill `offers.oem` from Woo ACF | reachable (manual; no schedule) |
| 42 | Artisan | oem:migrate-to-json | Copy `eurocodes.oem_code` to `oem_codes` JSON | reachable (manual one-off) |
| 43 | Artisan | eurocodes:sync-models | [DEPRECATED] generate Eurocodes/links from offers | reachable (manual; marked deprecated) |
| 44 | Seeder | database/seeders/OfferResourcesSeeder.php | Creates sample producers, colours, equipment, types, cars, one offer (uses `User::find(2)`) | unreachable by default: `$this->call(OfferResourcesSeeder::class)` is commented out in DatabaseSeeder.php; can only be run by hand |
| 45 | Model/table | app/Models/OfferModels/OfferExtras.php, `offer_extras` | Name-only table | unreachable (no callers) |
| 46 | Model | app/Models/CarModels/CarBrand.php, `car_brands` | Brand model | unreachable (table never created, only imports) |

No scheduled task touches this data: `routes/console.php` schedules only `UpdateFullDataOfActiveAdvertsCommand` (twice daily, 6 and 16) and a `queue:work --queue=otomoto,default` every minute (jobs 35/38 run there). No observers, listeners or event classes exist for Car/Eurocode/taxonomies (no `app/Observers`, `app/Listeners`, `app/Events` directories; `OfferUpdatedEvent`/`OfferDeletedEvent` are Offer-level). No routes: `routes/web.php` only redirects to `admin` and `/login`; `routes/api.php` has `/user` only. No global search is defined on any of the resources (no `getGloballySearchableAttributes` outside Shield RoleResource). No tests cover this data (`tests/` has only Jetstream/auth tests and ExampleTest). The WordPress folder has a plugin `allglass-api` with a single `verify-connection` REST route and no vehicle/eurocode code (the CPTs likely come from ACF/Pods; not defined in this repo, unknown).

## Contradictions and defects
- `EditOffer::beforeCreate` (promote/delete temp taxonomy rows) is a `CreateRecord` hook name; a Filament `EditRecord` page calls `beforeSave`, not `beforeCreate`. So the cleanup in Findings may never run on edit. `defect?`, from reading code only; I did not trace Filament's vendor page. Evidence: `EditOffer.php:78`. Tag: defect?
- `FetchWooProducts::handleCars` does `Car::find($modelID)->pluck('id')` and dereferences `$wordpressSync->syncable_id` without a null check (a missing sync row would throw). Tag: defect? Evidence: `FetchWooProducts.php:266-273`.
- `GetOemsFromWordpressOffers` fills `offers.oem`, but all display and export read `eurocodes.oem_codes` through `getPrimaryOem`. The data it imports goes nowhere visible. Tag: defect?/decide. Evidence: `SynchOemCodeFromWordpressJob.php:53-58`, `Offer.php:468-477`.
- Placeholder "eurocode, nr OEM lub SKU" in MyOffers promises OEM search but only `eurocodes.code` (and sku) are searchable. Tag: defect?. Evidence: `MyOffers.php:63`, `EurocodeTextColumn.php:13`.
- The `eurocode_offer` link is not created by the WordPress import, so imported offers have only the legacy `offers.eurocode` string until a matching action runs. Tag: inferred (no code attaches them; whether a later job fills them: not found).
- `routes/console.php:7` imports `App\Console\Commands\UpdateStatusOfExpiredOffers`, which does not exist in `app/Console/Commands` (it is imported only, so no runtime error). Out of scope; see Leads.

## Leads outside scope
- Offer status lifecycle, expiry and `OfferAcceptedAction` (Offers module).
- `wordpress_syncs` morph table and `WordpressSyncablePost` contract (integration module).
- `OtomotoMetadataService` meta keys `otomoto_offer_type_mapping`, `otomoto_shipping_mapping` (Otomoto module).
- `User.shipping_information` rich-text field (Users/Settings) overrides ShippingMethod text in Otomoto descriptions.
- `app/Models/WordpressSync.php` appears to carry a Filament column definition (model mixing UI).
- Three ShieldSeeder files (`ShieldSeeder`, `_old`, `_old2`); permissions for `*_car`, `*_eurocode` live there (unread).
- `_local_stuff/find_misassigned_offers.sql` (ops query on `offers` and `changes_logs`; not about this data).
- `SECURITY_INCIDENT_2026-10.md` and a `.env` with prod-like DB name `allglass_prod` in V1 root (not opened beyond DB_ names, DB credentials not read or used).

## Could not establish
- Local V1 database or dump: none inside V1 (only `_local_stuff/find_misassigned_offers.sql`, a query). `.env` points to `DB_HOST=mysql`, `DB_DATABASE=allglass_prod`, a Docker host name that does not resolve here (`getent hosts mysql` empty). A local `/var/run/mysqld/mysqld.sock` exists, and `mysql` is installed, but I did not connect: it would need the credentials from V1's `.env` and I cannot confirm that it is a V1 copy rather than something else. An unrelated dump `/mnt/d/wsb_k61_p1/wsb_k61_p1.sql` exists (not V1). Row counts: NOT obtained. A business answer is needed: "is a read-only V1 DB copy available, and may we use the local MySQL?".
- Policy binding for Eurocode and ShippingMethod, and which of the duplicate taxonomy policies is registered: I did not read `AppServiceProvider`/Filament providers in full, and Shield seeders. Tried: `grep Policy|Gate::` in `app/Providers`, which gave no explicit registration (Laravel auto-discovery by name probably binds `app/Policies/*Policy` for models in `App\Models`, but the OfferModels ones are in a sub-namespace, so which copy applies is runtime-dependent). A runtime check would settle it.
- Whether the WordPress side defines CPTs `vehicles`, `glass-colors`, `glass-producents`, `glass-accessories` (and ACF field names): not in V1 (plugin `allglass-api` only has `verify-connection`). Needs the live WordPress or an ACF export.
- Whether the `EditOffer::beforeCreate` cleanup runs (see Contradictions): needs a runtime test or reading Filament's `EditRecord` (vendor).
- Whether `ViewCar` shows relation managers as the Shared one or is blocked by permissions: not traced beyond `ViewCar.php:48`.
- The last part of `Offer::canBeWooSynch` (after line 330) and the `Colors/Producers/Equipment` import handlers were read only in part.

## Coverage ledger
- Searched (all spellings, in app/, routes/, config/, database/, resources/, tests/, bootstrap/, lang/, composer.json): `Car`, `CarBrand`, `car_brands`, `cars`, `car_offer`, `car_eurocode`, `eurocode_car` (no hit as a name), `Eurocode`, `EurocodeOffer`, `eurocodes`, `eurocode_offer`, `eurocode`, `alt_eurocodes`, `without_eurocode`, `eurocode_alt`, `oem`, `oem_code(s)`, `Oem`, `vim`, `vin` (no `vin` column: only "vim"), `OfferColor`, `offer_colors`, `offer_color_id`, `OfferProducer`, `offer_producers`, `offer_producer_id`, `OfferType`, `offer_types`, `offer_type_id`, `OfferExtras`, `offer_extras`, `OfferEquipment`, `offer_equipment`, `offer_offer_equipment`, `ShippingMethod`, `shipping_methods`, `shipping_method_id`, `shipping`, `OfferEurocodeRelationService`, `offersViaEurocodesQuery`, `eurocodeCarsQuery`, `getEurocodeCars`, `DonorCar`, `DonorMake`, `DonorModel`, `DonorGeneration`, `donor_`, Polish labels (`Baza pojazd`, `Eurokody`, `Kolory`, `Producenci`, `Rodzaje szyb`, `Dodatki`, `Wyposa`, `Metody wysyłki`, `eurokod`); plus `GloballySearchable|globalSearch|recordTitleAttribute`, `Policy|Gate::`, `Schedule::`, `onDelete|cascade|constrained|foreign`, `observe|Event::listen`, `tmp_session`, `wordpressSync|WordpressSync`, `acf|meta_data`; `find`/`ls` for dumps and sqlite files.
- Examined (read in full or the relevant ranges): `Models/Offer.php` (taxonomy/eurocode parts), `OfferCreateForm.php`, `OfferEditForm.php` (partly), `UserOfferCreateForm.php` (partly), `SelectEurocodesPartial.php`, `EurocodeTextColumn.php`, `ReorderEurocodesViewAction.php` (grep), `CreateOffer.php`, `EditOffer.php`, `ViewOffer.php` (ranges), `OfferCreatedByUserAction.php`/`OfferEditedByUserAction.php` (grep + ranges), `SubmissionResource.php` (grep), `ViewSubmission.php` (ranges), `OfferResource.php`/`FullListOffer.php`/`MyOffers.php`/`FullListOfferTable.php` (filters, columns), `ListCars.php`, `CarPolicy.php`, `WooProductDTO.php`, `SyncProductToWordpressAction.php`, `SyncProductCategoryAction.php`, `WoocommerceApiService.php` (187-216), `BulkSynchProductToWooAction.php`, `FetchWordpressDataOrchestrator.php`, `FetchWordpressCars.php`, `FetchWooOfferTypes.php`, `FetchWooProducts.php` (ranges), `OtomotoCreationService.php` (grep), `OtomotoDonorCarMatchService.php`, `OtomotoOptions.php` (ranges), `config/otomoto.php`, `routes/*.php`, `routes/console.php`, `DatabaseSeeder.php`, `OfferResourcesSeeder.php`, `GetOemsFromWordpressOffers.php`, `SynchOemCodeFromWordpressJob.php`, and all migrations touching these tables (cars, car_brands, offers, offer_extras, offer_equipment, offer_offer_equipment, car_offer, shipping_methods, offer_types shipping, eurocodes, tmp_session, donor_*, oem_codes).
- Matched, not examined in depth: `UpdateProductToWordpressAction.php` (twin of Sync…, checked by grep), `UpdateProductCategoryAction.php`, `FetchWordpress{Colors,Equipment,Producers}.php` (same structure as Cars by grep), `OtomotoOffer*` bulk actions (eurocode used only in confirmation lists), `OfferEditForm.php`/`OfferCreateForm.php` remaining layout parts, `UserReservedOffersPage`/`AdminReservedOffersPage` (they only use the shared table schemas), `StockSaleTab.php` (shows `shipping.name`), `ViewOfferType.php`, `ShieldSeeder*.php`, `lang/vendor/*` (framework strings, not relevant), anchors (covered by r01/r02).
