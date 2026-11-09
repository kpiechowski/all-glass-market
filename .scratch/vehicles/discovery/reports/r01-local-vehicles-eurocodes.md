# r01 — local: Vehicles and eurocodes

Question: What can users and the system do with vehicles and eurocodes in V1, and how are cars, eurocodes and offers linked? · V1 commit: 511ce3b

## Summary
- A car is a flat record: free-text `brand`, `model`, `type` (version), `year` (string) and a unique `name`. There is no brand or model table and no generation. `CarBrand` is dead code.
- Eurocode is a bare identifier: `code` (unique), `oem_codes` (JSON list) and a legacy `oem_code`. It carries no glass parameters (type, colour, equipment). Those live on the Offer.
- Two links exist between offers and cars. (1) `car_offer` pivot (Offer::cars, Car::offers). (2) Offer to eurocode (`eurocode_offer`, with priority) to car (`car_eurocode`).
- The eurocode-based link is the one the cars list "Liczba ofert" counts. The car view page lists offers through the legacy pivot. The two can disagree.
- Source of truth for eurocode↔car fitment: `car_eurocode`, edited from the eurocode form. The `car_offer` pivot is legacy, filled from Woo import.
- No eurocode command is scheduled. All three are manual.

## Findings

### A car is defined by a free-text name plus brand/model/type/year
- Tag: confirmed · Impact: normal
- What happens: the cars table has `name` (unique, not null), `slug`, `brand`, `model`, `type`, `year` (string). The last four are nullable. No FK to a brand table.
- Evidence: database/migrations/2024_10_06_144444_create_cars_table.php:16-23 Schema::create('cars'); app/Models/Car.php:75-150 getForm.

### Brand table and CarBrand are dead
- Tag: confirmed · Impact: normal
- What happens: the car_brands migration body is commented out, so the table is never created. CarBrand::cars hasMany would need `car_brand_id`, which does not exist on cars.
- Evidence: 2024_10_06_144444_create_car_brands_table.php:14-19 (commented); app/Models/CarModels/CarBrand.php:14 cars(); cars migration has no brand_id.

### Car form: validation and helpers
- Tag: confirmed · Impact: normal
- What happens:
  - "Marka pojazdu" is required, max 255, with a datalist of existing brands.
  - "Model" has a datalist narrowed by brand, max 255.
  - "Typ" has a datalist narrowed by brand and model.
  - "Rocznik" is free text with no validation.
  - "Nazwa pojazdu" is required, unique (ignoring the current record), max 255, and must not contain `_` (regex `^[^\_]+$`).
  - A prefix action "Wygeneruj nazwę automatycznie" builds the name as `brand | model | type | year` (empty parts skipped) and also sets `slug` in form state. `slug` is not a form field, so the form never saves it. See the slug finding below.
- Evidence: app/Models/Car.php:81-148.

### Slug is generated from name once, on create only
- Tag: confirmed · Impact: normal
- What happens: a `created` hook sets `slug = Str::slug(name)` and saves again. The `updated` hook is commented out, so the slug does not follow later renames.
- Evidence: Car.php:56-69 booted().

### Car::booted overrides the taxonomy trait hook, so Car updates do not auto-push to WordPress
- Tag: inferred · Impact: high (external system)
- What happens: the trait IsOfferTaxonomyModel defines booted(), which on `updated` calls `afterUpdateSyncAction`. That calls WordpressApiService::updateModel when the model has `wordpressSync` with `auto_update` and is not Pending. Car defines its own `booted()`, and a class method overrides the trait method. So the trait's `updated` hook is not registered for Car. Only the slug-on-create hook runs.
- This follows from PHP semantics and was not run. Car does not use the trait's alias.
- Evidence: Car.php:23-25 uses the trait; Car.php:56 own booted; app/Filament/Traits/IsOfferTaxonomyModel.php:14-31.

### Car is pushed to WordPress as a `vehicle(s)` post
- Tag: confirmed · Impact: high (external system)
- What happens:
  - Car implements WordpressSyncablePost.
  - It is not synced if `name` is empty or status is Pending (`ResourceStatus::Pending`).
  - JSON sent: `title`=name and `acf` {`brand`, `model`, `year`, `version`=type}.
  - Endpoint is `vehicle` when APP_ENV=local, otherwise `vehicles`.
  - The cars table has no `status` column in its create migration. A later migration may add it, which I did not check, and the `canBeSync` status check depends on it.
- Evidence: Car.php:152-186.

### Cars are only logged by name and model
- Tag: confirmed · Impact: normal
- What happens: activity log records only `name` and `model`. Changes to brand, type and year are not logged.
- Evidence: Car.php:188-192.

### Admin CRUD on cars ("Baza pojazdów", in the Offers cluster)
- Tag: confirmed · Impact: normal
- What happens:
  - An admin can list cars, search by name, model and "Rocznik", and sort by "Liczba ofert" and created date. The default sort is newest first.
  - Create and edit open in a slide-over modal. View opens a page showing name, a WordPress sync section, and "Dane" (brand, model, type, year).
  - Single delete and bulk delete are available. Edit redirects to the index.
  - A column "Synchronizacja z WP" is hidden by default.
  - No filters. No per-resource permission code was seen in these files (not investigated).
- Evidence: app/Filament/Resources/CarResource.php:42-125; Pages/ListCars.php, ViewCar.php. The create and edit routes are commented out, so only the modal paths exist.

### Cars list "Liczba ofert" counts offers reached via eurocodes
- Tag: confirmed · Impact: normal
- What happens: it counts DISTINCT offers joined through `eurocode_offer` and `car_eurocode`. It ignores the `car_offer` pivot. Badge is green if >0, else amber.
- Evidence: CarResource.php:46-57 modifyQueryUsing; Car.php:41-49 scopeWithOffersCount, which is unused (the usage is commented out in ListCars.php:27).

### Car view page lists offers via the legacy car_offer pivot
- Tag: confirmed · Impact: normal
- What happens: ViewCar uses `Resources\Shared\OffersRelationManager` with relationship `offers`, which is Car::offers belongsToMany(Offer) on the default pivot `car_offer`. Offers linked only through eurocodes do not appear there.
- Another `OffersRelationManager` exists under CarResource/RelationManagers (columns incl. eurocode, price, status, and a "Rodzaj szyby" filter). It is not referenced (commented out in CarResource.php:10).
- Car::offersViaEurocodesQuery has no callers.
- Evidence: Pages/ViewCar.php:18,46-49; Car.php:29-31, 33-40; CarResource.php:10,113-117.

### Deleting a car removes its eurocode fitments but not offers; no cascade on car_offer
- Tag: confirmed · Impact: high (deletion)
- What happens:
  - `car_eurocode.car_id` has cascadeOnDelete, so deleting a car deletes its fitment rows.
  - `car_offer` is created with plain `foreignIdFor` (no constraint, no cascade). Deleting a car leaves orphan `car_offer` rows.
  - Deleting an eurocode cascades to `eurocode_offer` and `car_eurocode`.
  - Cars have no soft delete.
- Evidence: 2025_08_30_131935_create_eurocodes_table.php:29-49; 2025_01_05_145235_car_offer_table.php:15-19; Car.php has no SoftDeletes.

### Eurocode: identity, uniqueness, normalisation
- Tag: confirmed · Impact: normal
- What happens:
  - A eurocode has a UUID string primary key (generated on create if absent) and `code`, unique at the DB level.
  - The admin form requires "Eurokod" (max 255, unique ignoring the current record). There is NO normalisation in the admin form. A hand-typed code is saved as typed (case and spaces kept), so `ABC 123` and `ABC123` are different rows.
  - Normalisation (uppercase, strip all whitespace) happens only in:
    - the lookup by user input: OfferEurocodeRelationService::normalizeInput;
    - the Submission "create eurocode" action;
    - the OEM lookup in SelectEurocodesPartial;
    - the deprecated sync command.
  - Mismatch risk for eurocodes created in the admin form (defect?, see below).
- Evidence: Eurocode.php:30-37 creating hook; migration :17-19; EurocodeResource.php:30-34; OfferEurocodeRelationService.php:76-79; ViewSubmission.php:190; SelectEurocodesPartial.php:201-214; Commands/GenerateEurocodesAndRelationFromOffers.php:162-172.

### OEM codes: JSON list on eurocode, tag input, no normalisation or uniqueness
- Tag: confirmed · Impact: normal
- What happens:
  - `oem_codes` is a nullable JSON column cast to array. It is edited as a tag input "Kody OEM" ("Wpisz kod i naciśnij Enter"; helper "Możesz dodać wiele kodów OEM – np. oryginalny i alternatywny.").
  - No uniqueness across or within eurocodes, and no normalisation on save.
  - The table searches OEM via MySQL `JSON_SEARCH(oem_codes,'one','%term%')`, a substring search that depends on case collation.
  - The legacy string `oem_code` column still exists, is still in `$fillable`, and there is a read-only accessor `oem_code` returning `oem_codes[0]`.
  - Lookups by OEM use whereJsonContains with the UPPERCASE, space-stripped value (exact element match), so an OEM stored lowercase or with spaces will not be found.
- Evidence: Eurocode.php:12-31; EurocodeResource.php:37-41,103-112; 2026_03_28_000001_add_oem_codes_to_eurocodes_table.php:17-18; OfferEurocodeRelationService.php:22-26.

### Find eurocode from user input matches code or any OEM
- Tag: confirmed · Impact: normal
- What happens: input is normalised, then a Eurocode is returned whose `code` equals it OR whose `oem_codes` contains it (first match). Empty input returns null. Callers were not traced here (cross-repo scope).
- Evidence: OfferEurocodeRelationService.php:15-27.

### Eurocode admin CRUD
- Tag: confirmed · Impact: normal
- What happens:
  - A list ("Eurokody") with search on code and OEM, and counts of "Oferty" and "Pojazdy" (colour badge, amber if 0).
  - Create via a header action. Edit via a row action, with a delete header action on the edit page. Bulk delete is available.
  - An edit page and a create page class exist, but their routes are commented out in getPages (only `index` is registered). Edit and create therefore depend on Filament modal actions inferred from the list page. This was not traced to runtime. Unverified: whether the row EditAction works without an edit route (Filament uses a modal when no page route exists, so it is likely).
  - The form: "Eurokod", "Kody OEM", "Kompatybilne pojazdy" (multi-select, searchable, relationship `cars`, search by name LIKE, 20 results).
  - No filters.
- Evidence: EurocodeResource.php:29-52, 95-150; Pages/*.php.

### Source of truth for fitment: car_eurocode (eurocode is the owner)
- Tag: confirmed · Impact: high
- What happens:
  - A fitment is a pair (car, eurocode), unique by the composite PK, many-to-many with timestamps.
  - It is written from the eurocode form "Kompatybilne pojazdy", from the Submission action (syncWithoutDetaching), from the Otomoto sync tab (not examined; it has a `cars` select, which is a lead), and from the deprecated backfill command.
  - Cars cannot be assigned to eurocodes from the car form. The car form has no eurocode field.
  - The offer carries no fitment of its own in the new model. Offer→cars is derived through its eurocodes.
- Evidence: Eurocode.php:57-61 cars(); migration :36-47; ViewSubmission.php:198-201.

### Offer↔eurocode link: ordered, with priority; touching the offer
- Tag: confirmed · Impact: normal
- What happens:
  - `eurocode_offer` has PK (eurocode_id, offer_id) and `priority` (default 0).
  - Offer::eurocodes is always ordered by priority ascending, so priority 0 is the primary eurocode.
  - The legacy backfill gave priorities 0, 10, 20... (primary then alternatives).
  - A pivot class `EurocodeOffer` touches the parent offer on pivot changes, firing the Offer updated event, but Offer::eurocodes has `->using()` COMMENTED OUT, so the touch pivot is not applied via the relation.
  - So the touch behaviour is not effective, unless it is used elsewhere (not searched).
- Evidence: Offer.php:420-427; Eurocode.php:50-55; Models/EurocodeOffer.php:11-21; migration :20-28.

### Eurocode carries no glass parameters
- Tag: confirmed · Impact: normal
- What happens: columns are only id, code, oem_code (legacy), oem_codes, timestamps. Type, colour, producer and equipment are on the Offer (`offer_color_id`, `offer_type_id`, `offer_producer_id`, equipment relation). To describe a eurocode's glass, the system picks a "best related offer": the newest non-Pending offer (incl. soft-deleted) with this eurocode, then the one with the most filled colour/type/producer/equipment.
- Evidence: migrations; OfferEurocodeRelationService.php:36-55 findBestRelatedOfferByEurocodeId; :57-77 findRelatedOfferByEurocodes (latest other offer sharing any eurocode, excluding the offer itself).

### Command eurocodes:sync-models (deprecated backfill from offers)
- Tag: confirmed · Impact: high (bulk data write)
- What happens:
  - For each offer (all, newest id first, chunked 500, `--dry` option) it reads `offer->eurocode` and `alt_eurocodes` (JSON, JSON string or delimited text).
  - Codes are normalised (uppercase, no spaces) and unique-ified. Missing eurocodes are created (code only). The offer is attached with priority 0, 10, 20... If already attached, the smaller priority wins.
  - Only when the offer has exactly one code: the offer's single `oem` is set into the eurocode `oem_code` (legacy column) if the eurocode has none, AND all `offer->cars` (car_offer pivot) are attached to the eurocode (syncWithoutDetaching).
  - This is how car_offer data was turned into car_eurocode fitments. Offers with alt eurocodes get no car fitments.
  - Marked @deprecated; writes the legacy column, not `oem_codes`.
  - Not scheduled; manual only.
  - Caveat: the "oem already set" check reads the accessor (`oem_codes[0]`), not the legacy column.
- Evidence: Commands/GenerateEurocodesAndRelationFromOffers.php:15-190; routes/console.php (no entry).

### Command oem:migrate-to-json
- Tag: confirmed · Impact: high (bulk data write)
- What happens: for eurocodes with a non-empty legacy `oem_code` and empty `oem_codes`, it sets `oem_codes = [oem_code]`, saved quietly. It is idempotent, supports `--dry` and chunking. It does not clear `oem_code`. Manual only.
- Evidence: Commands/MigrateOemCodesToJsonCommand.php:12-62.

### Command wordpress:sync-oems fills Offer.oem (not Eurocode) from WooCommerce
- Tag: confirmed · Impact: high (external system, data write)
- What happens:
  - It selects offers with NULL `oem`, SKU NOT LIKE 'AG%', and a Woo sync link.
  - It dispatches SynchOemCodeFromWordpressJob in chunks of 10 on queue `oem_codes`.
  - The job GETs Woo `products?sku=…`, reads `acf.oem`, and if present and the offer's `oem` is empty, sets `offer.oem` (saved quietly). It logs when not found or empty.
  - It does NOT write to the eurocodes table. Offer.oem reaches `eurocodes.oem_codes` only via the old backfill (single code, legacy column) or manual entry. No automatic path from Offer.oem to `oem_codes` was found in the anchors.
  - Not scheduled. The scheduler worker processes only the queues `otomoto,default`, and routes/console.php comments say the named queue `oem_codes` stays manual. So a worker must be started by hand.
- Evidence: Commands/GetOemsFromWordpressOffers.php:15-28; Jobs/Offer/SynchOemCodeFromWordpressJob.php:30-62; routes/console.php:16-24.

### Woo import attaches cars to offers via car_offer
- Tag: confirmed · Impact: normal (one hop out)
- What happens: FetchWooProducts::handleCars maps WP vehicle ids via `wordpress_syncs` (syncable_type App\Models\Car) and does `$offer->cars()->attach(...)`. There is no null check on the sync row, so a missing mapping throws. `Car::find($id)->pluck('id')` is a misuse of pluck, and the `if ($model)` check is always truthy.
- Evidence: app/Services/ApiService/Jobs/FetchWooProducts.php:255-276.

### Submission flow can create a eurocode and attach it to the offer
- Tag: confirmed · Impact: normal (one hop out)
- What happens:
  - From a submission view, an action creates or finds (firstOrCreate by normalised code) a eurocode, using the OEM list only when creating.
  - It adds the selected cars as fitments without detaching, shows "Eurokod utworzony", then replaces the offer's eurocodes with this one (`sync`, so ALL previous eurocodes of the offer are dropped) and shows "Eurokod przypisany do oferty".
  - This is the only place found where a eurocode is typed in a free flow.
- Evidence: Resources/SubmissionResource/Pages/ViewSubmission.php:185-210.

### Offer form: find eurocode by OEM
- Tag: confirmed · Impact: normal (one hop out)
- What happens: an action takes an OEM, normalises it, and looks up a eurocode via whereJsonContains. An empty value gives the warning "Podaj numer OEM". No match gives the danger "Nie znaleziono eurokodu dla podanego OEM". Otherwise the eurocode is added to the offer's selection (the rest was not read; the offer flow belongs to r04).
- Evidence: Offer/.../Partials/SelectEurocodesPartial.php:198-225.

## Contradictions and defects
- defect? Admin eurocode form does not normalise `code`. All other writers uppercase and strip spaces, and lookups compare against the normalised value. A hand-entered `abc 123` can never be found by findEurocodeFromUserInput. Evidence: EurocodeResource.php:30-34 vs OfferEurocodeRelationService.php:76.
- defect? Two different offer counts for a car (list via eurocodes; view page via car_offer).
- defect? ActionFormSchema `code` uses `unique(table:'eurocodes', column:'code')` without ignoreRecord. It is used for creating in an action, so this is fine for create only. Where it is used was not traced (inferred).
- defect? `EurocodeOffer` pivot with `$touches` is not wired (`using` commented out) — dead.
- defect? Orphan `car_offer` rows after a car delete (no FK).
- defect? FetchWooProducts::handleCars dereferences a possibly-null sync row.
- Observation: `Car::getForm` helper has a stray `$set('slug', ...)` for a non-existent field.

## Leads outside scope
- app/Filament/Resources/OtomotoOfferResource/Components/OtomotoSynchronizationTab.php:145-160 has a `cars` select that writes car ids (probably eurocode fitment). Not read.
- Possible status column on cars from another migration (Car::canBeSync uses `status`). Check migrations adding it.
- Offer->`eurocode`, `alt_eurocodes` and `oem` legacy columns on offers (backfill reads them), and the Offer `updated` event wiring (OfferUpdatedEvent). Belongs to r04 or a cross-repo mission.
- SelectEurocodesPartial full flow, OfferResource EurocodeTextColumn.
- Woo export of cars/eurocodes keys: not searched.

## Could not establish
- Whether Car's `updated` WP auto-push is truly absent at runtime (inferred from PHP trait precedence; runtime check would settle it).
- Whether the eurocode row EditAction works with the `edit` route commented out (Filament modal fallback expected; runtime would settle it).
- Which users/roles may manage cars and eurocodes (policies/Shield not examined; outside the anchors).
- Whether any of the three commands has been run in production, or whether the eurocodes were maintained by hand afterwards. Needs a business answer.

## Coverage ledger
- Searched: car_offer, car_eurocode, ->cars(), 'cars', oem_codes, sync-oems, eurocodes:sync, oem:migrate, offersViaEurocodesQuery, withOffersCount, Schedule/command in routes/console.php, getForm.
- Examined: app/Models/Car.php; app/Models/CarModels/CarBrand.php; app/Filament/Resources/CarResource.php and Pages/{Create,Edit,List,View}Car; Resources/CarResource/RelationManagers/OffersRelationManager.php; app/Models/Eurocode.php; app/Models/EurocodeOffer.php; Clusters/Offers/Offers/Resources/EurocodeResource.php and Pages/{Create,Edit,List}Eurocode(s); app/Services/Offer/OfferEurocodeRelationService.php; the three commands; migrations create_car_brands, create_cars, car_offer, create_eurocodes (includes eurocode_offer and car_eurocode, so there is no separate pivot migration), add_oem_codes; SynchOemCodeFromWordpressJob; routes/console.php; Traits/IsOfferTaxonomyModel.php (head, lines 1-60 only); Offer::eurocodes and Offer::cars; excerpts of FetchWooProducts, ViewSubmission, SelectEurocodesPartial.
- Matched, not examined: Resources/Shared/OffersRelationManager.php (only relationship and imports checked), Traits/IsOfferTaxonomyView, OtomotoSynchronizationTab.php, WordpressApiService, OfferResource eurocode components (other missions).
