# Vehicles (working name) — discovery

<!-- Working document for the module-discovery skill. Only the manager writes it.
     Item ids are stable and never reused. Every finding cites a report (rNN) and its V1 evidence.
     Tags: confirmed · observed · inferred · ambiguous · unknown · defect? · decide -->

## A. Metadata

- **Status:** ready-for-grilling
- **Next actions:** paused at `ready-for-grilling` (2026-11-09, user decision). No separate grilling of this module yet: Offers discovery runs first, then one domain grilling on both documents (prompt: `.scratch/vehicles/domain-grilling-prompt.md`, to be updated with Offers), then module grilling per `grilling.md`. G-09: the V1 production dump is now loaded locally (MySQL container, database `allglass_v1_prod`); a data-shape mission for this module is still to run before grilling.
- **Started:** 2026-11-08 · **Last updated:** 2026-11-08
- **V1:** `/home/kp/laravel/projekty/AllGlass/` @ `511ce3b` (dirty: `docker-compose.yml` modified, `SECURITY_INCIDENT_2026-10.md` untracked; neither touches this module)
- **V2 state:** module absent. Folder name `vehicles` is provisional; the module name is a grilling decision.
- **Prior material (leads, `inferred`):** `docs/archive/v1-port/modules/vehicles/scan.md` (scope list only), `docs/archive/v1-port/modules/offers/scan.md` (scope list), `docs/archive/v1-port/refactor-plan.md` boundary table, `.scratch/roadmap.md` row 5, `.scratch/settings/decision.md` (WooCommerce items carried forward).

### Scope

- **Apparent responsibility:** the reference data that describes a piece of glass and is later attached to it: vehicles (brands, models), eurocodes with their OEM codes and vehicle fits, glass types, colours, producers, equipment/extras. Not only "cars" (user, 2026-11-08).
- **Framing question from the user (for grilling, not settled):** V1's Offer is both the physical glass and the listing. V2 may split them: a **Glass** (a preset keyed by eurocode, carrying its encoded parameters, linked to vehicles/VINs) and an **Offer** (the sellable item/listing). Discovery must show, per attribute, whether it belongs to the eurocode/glass definition, the physical item, or the listing, and whether one "catalogue/resources" area with sub-tabs or separate homes fits.
- **Anchors:**
  - Vehicles: `app/Models/Car.php`, `app/Models/CarModels/CarBrand.php`, `app/Filament/Resources/CarResource.php` (+ `Pages/`, `RelationManagers/OffersRelationManager.php`), migrations `2024_10_06_144444_create_car_brands_table`, `2024_10_06_144444_create_cars_table`, `2025_01_05_145235_car_offer_table`.
  - Eurocodes: `app/Models/Eurocode.php`, `app/Models/EurocodeOffer.php`, `app/Filament/Clusters/Offers/Offers/Resources/EurocodeResource.php` (+ `Pages/`), `app/Services/Offer/OfferEurocodeRelationService.php`, commands `GenerateEurocodesAndRelationFromOffers`, `GetOemsFromWordpressOffers`, `MigrateOemCodesToJsonCommand`, migrations `2025_08_30_131935_create_eurocodes_table`, `2026_03_28_000001_add_oem_codes_to_eurocodes_table`.
  - Glass attributes ("OfferTaxonomies"): `app/Models/OfferModels/{OfferColor,OfferProducer,OfferType,OfferExtras}.php`, `app/Models/OfferEquipment.php`, `app/Filament/Clusters/Offers/Resources/OfferTaxonomies/*`, migrations `create_offer_colors_table`, `create_offer_producers_table`, `create_offer_types_table`, `add_shipping_to_glass_type`, `create_offer_extras_table`, `create_offer_equipment_table`, `create_offer_offer_equipment`.
  - Boundary: `app/Models/ShippingMethod.php` + `ShippingMethodResource` (linked to glass type), Offer columns `eurocode`, `alt_eurocodes`, `oem`, `vim`, `year`, `without_eurocode`, `offer_producer_id`, `offer_color_id`, `offer_type_id` (`2024_10_06_151733_create_offers_table`).
- **Vocabulary:** `Car`, `CarBrand`, `cars`, `car_brands`, `car_offer`, `Eurocode`, `EurocodeOffer`, `eurocodes`, `eurocode_offer`, `eurocode`, `alt_eurocodes`, `without_eurocode`, `oem`, `oem_code(s)`, `vim`/`vin`, `OfferColor`, `offer_colors`, `offer_color_id`, `OfferProducer`, `offer_producers`, `offer_producer_id`, `OfferType`, `offer_types`, `offer_type_id`, `OfferExtras`, `offer_extras`, `OfferEquipment`, `offer_equipment`, `offer_offer_equipment`, `ShippingMethod`, `OfferEurocodeRelationService`, `offersViaEurocodesQuery`, `getWordpressMetaKey`, `getWordpressApiEndpoint`, `jsonStructure`, `canBeSync`, `DonorCar`/`DonorMake`/`DonorModel`/`DonorGeneration` (Otomoto car catalogue). Polish labels: "Baza pojazdów", "Eurokody", "Kolory", "Producenci", "Rodzaje szyb", "Dodatki / Wyposażenie", "Metody wysyłki"; cluster `Offers`.
- **Boundary assumptions:** cars and eurocodes exist independently of offers (refactor-plan boundary table, roadmap). The taxonomies were listed under Offers in the archive scan; the user moved them into this discovery. Shipping methods: probably Offers/sales, kept as a boundary item.
- **Out of scope:** WooCommerce/WordPress sync (integration, later) — recorded only as "what is exported and how it is keyed", not analysed; Otomoto (dropped, ADR/roadmap), except whether its Donor* car catalogue feeds V1 vehicle data; the Offer lifecycle, prices, reservations, sales (Offers discovery).
- **Limitations:** static reading of V1; no V1 runtime or production data unless an investigator finds a read-only DB.

## B. Module overview

- **Vehicles (r01):** a car is a flat, free-text record (brand, model, type/version, year, unique name). There is no brand/model/generation dictionary; `CarBrand` is dead.
- **Eurocodes (r01):** a eurocode is a bare identifier (unique `code` plus a list of OEM codes). It carries **no glass parameters**; type, colour, producer and equipment live on the Offer, and the system "describes" a eurocode by borrowing them from its best related offer (BR-06).
- **Fitment (r01):** which cars a eurocode fits is held on the eurocode (`car_eurocode`). A second, legacy offer→car link (`car_offer`) still exists and is fed by the Woo import, so V1 has two answers to "which offers fit this car" (Q-02).
- **Glass dictionaries (r02):** four live dictionaries: producer, colour, glass type ("Rodzaje szyb") and equipment ("Dodatki / Wyposażenie"). They share one shape (unique name, slug, status Pending/Active) and an approval step. `OfferExtras` is dead. Shipping methods are a separate priced list that a glass type defaults and an offer may override; only Otomoto reads it.
- **How an offer gets its glass description (r04):** the offer↔eurocode link (ordered, first = primary) is authoritative. An admin picks eurocodes from the register; a seller types free text that is matched to a eurocode (by code or OEM) after save. Colour, type and equipment are then **copied from another offer with the same eurocode** (four places). Producer, year and condition are set per offer. Cars reach an offer only through the eurocode's fitment. The legacy offer columns `eurocode`, `alt_eurocodes`, `oem`, `vim` are no longer written by live forms.
- **Reading for the Glass vs Offer question (manager, not a decision):** V1 already behaves as if there were a glass definition per eurocode (fitment and OEM on the eurocode; colour/type/equipment "inherited" from sibling offers), but it never stored one, so the definition is reconstructed from offers each time (BR-06, W-02). Per-piece data in V1: producer, year of production, condition (new/used/outlet + 1–5 rating), quantity, photos. See Q-09.
- **Where the data is used (r03):** almost entirely through the Offer — forms, 11 offer tables (eurocode column), filters by type/producer/colour, the "Bez eurocodu" tab, the offer view, the submission review, the WooCommerce export/import and Otomoto adverts. No routes, API, schedules, observers, global search or tests touch it. V1's reference data (cars, colours, producers, equipment, types) was originally **imported from WordPress**, and the import fills only the legacy eurocode strings, not the links.
- **Industry view (r05, web, trade-documented):** a Eurocode (ARGIC, since 1993; up to 15 characters, 17 for codes created from 2025; both formats coexist) encodes the vehicle model block, the **glass position** (A windscreen, B rear, L/R side…), the **tint** and shade band, body/door location for side glass, **features** (antenna, camera, heated, sensor, HUD, VIN window, encapsulation…) and a modification code. It does **not** encode the producer of the piece, OE/aftermarket status, production date or condition. OE numbers, Eurocodes and producer numbers relate many-to-many. Marketplaces and salvage yards separate the catalogue product (identifier, attributes, fitment) → the unique stock unit (condition, producer, date, photos) → the listing (price, seller, channel, status). Public decoding tables disagree, so storing the code as an opaque identifier is safer than parsing it.
- **Consequence for the framing question (manager reading, not a decision):** V1's offer-level dictionaries type, colour and equipment are exactly what a Eurocode encodes, and V1 already copies them between offers with the same eurocode (Q-09); producer, year and condition are what a Eurocode leaves out and V1 keeps per offer. The evidence supports the user's hypothesis that type, colour and equipment belong to the glass definition and producer/year/condition to the piece. The decision stays with the user.

## C. Functional inventory

| ID | Functionality | Entry points | Workflow | Verified | Confidence | Open |
|---|---|---|---|---|---|---|
| F-01 | Vehicle register ("Baza pojazdów"): list, search, create, edit, delete cars | Filament `CarResource` (modal create/edit, view page) | n/a: plain CRUD; rules in BR-01/02/07 | spot (BR-07) | high | — |
| F-02 | Eurocode register ("Eurokody"): list with offer/vehicle counts, search by code and OEM, create, edit, delete | Filament `EurocodeResource` (index only; modal actions) | n/a: plain CRUD; rules in BR-03/04 | — | high | Q-01, G-05 |
| F-03 | Vehicle fitment: assign compatible cars to a eurocode | eurocode form "Kompatybilne pojazdy"; Submission action (F-10); deprecated command (F-07); Otomoto tab (G-04) | n/a: relation edit in a form | spot (cascade) | high | G-04 |
| F-04 | Offer ↔ eurocodes link with priority (primary + alternatives) | offer forms, propagation, duplicate action | W-01 | — | high | Q-05 |
| F-05 | Find a eurocode by typed code or by any OEM code | seller eurocode field (after save); admin "find by OEM" action is uncalled (r04) | W-01 | — | high | Q-01 |
| F-06 | Describe a eurocode's glass by borrowing attributes from its best related offer | system, `OfferEurocodeRelationService` | W-02 | — | high | Q-09 |
| F-07 | Backfill eurocodes and fitments from offers (`eurocodes:sync-models`, deprecated) | artisan, manual | n/a: one-off migration tool, behaviour in BR-08 | — | high | G-02 |
| F-08 | Copy legacy single OEM code into the OEM list (`oem:migrate-to-json`) | artisan, manual | n/a: one-off migration tool | — | high | G-02 |
| F-09 | Pull offer OEM numbers from WooCommerce (`wordpress:sync-oems`) | artisan + queued job, manual worker | n/a: integration, out of scope (record only) | — | high | G-02 |
| F-10 | From a submission: create/find a eurocode, add fitments, replace the offer's eurocodes with it; review the seller's pending dictionary values ("Specyfikacja szyby") | Filament `ViewSubmission` action "Dodaj eurokod" and section | n/a: single action; effect in BR-09 | spot | high | Q-04 |
| F-12 | Glass dictionaries (producer, colour, glass type, equipment): list with "Oczekujące" tab, view, create, edit, delete, approve ("Zatwierdź") | Filament `OfferTaxonomies/*` resources | n/a: CRUD + one-step approve (BR-10, BR-11) | — | high | Q-10, G-06 |
| F-13 | Propose a new dictionary value while creating an offer (pending until approved) | offer create form, producer only (live); wizard for type/colour/equipment unreachable | n/a: inline create option; effect in BR-11 | spot (Q-06) | high | — |
| F-14 | Shipping methods ("Metody wysyłki"): CRUD; glass type default; offer override | Filament `ShippingMethodResource`; type and offer forms (admin) | n/a: CRUD; rule BR-14 | — | high | Q-12 |
| F-15 | Dictionaries exported to WordPress/Woo (colour, producer, equipment as posts; type as product category) | model sync hooks, detail-page sync actions | n/a: integration, out of scope (record only) | — | high | — |
| F-16 | Assign eurocodes to an offer: admin ordered multi-select; seller free text matched by code or OEM after save | offer create/edit forms | W-01 | — | high | Q-07, Q-08 |
| F-17 | Auto-fill colour, type, equipment from another offer with the same eurocode | admin form (live), after create, admin edit, seller edit | W-02 | — | high | Q-09 |
| F-18 | Offer without eurocode: free vehicle description, seller picks type and colour by hand | toggle "Nie posiadam eurocodu / kodu OEM" | W-03 | — | high | Q-13 |
| F-19 | Reorder an offer's eurocodes (set the primary) | offer view, admin action "Ustal priorytety eurokodów" | n/a: single action rewriting priorities | — | high | — |
| F-20 | Show and filter offers by catalogue data: eurocode badges (11 tables), filters "Typ szyby"/"producent"/"kolor", tab "Bez eurocodu", offer view with eurocodes, OEM (admin), cars, equipment | offer lists, `FullListOffer`, `MyOffers`, Livewire table, offer view, journal column | n/a: display and filtering owned by Offers; listed so the catalogue keeps these reads possible | — | high | Q-15 |
| F-21 | Import reference data and offers from WordPress (cars, equipment, producers, colours, types, then products) | Settings page "Pobierz Oferty z Woocommerce" → queued jobs | n/a: integration, out of scope (record only); matters for data migration | spot (no links written) | high | X-03 |
| F-11 | Car pushed to WordPress as a `vehicles` post (title + brand/model/year/version) | model sync hooks | n/a: integration, out of scope (record only) | — | med | Q-03 |

## D. Business rules and domain knowledge

### Confirmed (`confirmed` / `observed`)

- **BR-01** A car has a required, unique name (no `_` allowed); brand is required; model, type and year are free text with suggestions from existing values; a helper can compose the name as `brand | model | type | year`. — F-01; r01, `Car.php:81-148`
- **BR-02** A car's slug is set from its name on create only and does not follow renames. — F-01; r01, `Car.php:56-69`
- **BR-03** A eurocode's code is unique in the database; a eurocode holds a list of OEM codes with no uniqueness or normalisation. — F-02; r01, eurocodes migration `:17-19`, `Eurocode.php:12-31`
- **BR-04** A fitment is one (car, eurocode) pair; deleting either side deletes the fitment. — F-03; r01, spot-checked `2025_08_30_131935_create_eurocodes_table.php:36-47`
- **BR-05** An offer's eurocodes are ordered by priority; priority 0 is the primary eurocode, the rest are alternatives. — F-04; r01, `Offer.php:420-427`, migration `:20-28`
- **BR-06** A eurocode carries no glass parameters; to describe it the system takes the newest non-pending offer with that eurocode (soft-deleted included), then the one with most of colour/type/producer/equipment filled. — F-06; r01, `OfferEurocodeRelationService.php:36-55`
- **BR-07** Deleting a car deletes its fitments but leaves its `car_offer` links behind (no foreign key); cars are hard-deleted. — F-01; r01, spot-checked `2025_01_05_145235_car_offer_table.php:15-19`
- **BR-08** The backfill turned offer data into catalogue data: codes from `offer.eurocode` + `alt_eurocodes` became eurocodes (normalised); only offers with exactly one code passed their cars and OEM to the eurocode. — F-07; r01, `GenerateEurocodesAndRelationFromOffers.php:15-190`
- **BR-10** Every glass dictionary value has a unique name without `_`, a slug set on create only, and a status Pending ("Oczekuje zatwierdzenia") or Active ("Aktywna"); an admin's new value defaults to Active, anyone else's is Pending. — F-12; r02, `OfferColor.php:27-44`, `IsOfferTaxonomyModel.php:22-48`
- **BR-11** A value proposed during offer creation is tied to the user's session; on create the chosen one stays Pending for approval and the user's other unchosen proposals are deleted; approving sets Active. — F-12, F-13; r02, `CreateOffer.php:55-80`; r04
- **BR-12** Producer, colour and type are 0..1 per offer; equipment is 0..n. Values are always picked, never free text. — F-12; r02, `Offer.php:498-562`
- **BR-13** Selects offer Active values only (plus the user's own session proposals); a seller's glass-type choice is further limited to the names PRZEDNIA, TYLNA, BOCZNA, SZYBA DACHOWA. — r02, r04, `OfferCreateForm.php:368-430`
- **BR-14** An offer's shipping method is its own, else its glass type's default. — F-14; r02, `Offer.php:507-530`
- **BR-15** Duplicating an offer keeps type, colour, equipment and eurocodes (with priority) and drops producer, year and SKU; quantity resets to 1. — r02, r04, `Offer.php:606-640`
- **BR-16** `year` is the glass's production year, not the car's; a seller sees it only for used/outlet glass, an admin always. Condition is new/used/outlet plus a 1–5 rating shown for used/outlet. — r02, r04, `OfferCreateForm.php:360-365`
- **BR-17** Cars reach an offer only through its eurocodes' fitment; WooCommerce publishing is refused when that yields no car ("Oferta nie posiada powiazanego pojazdu"). — F-03, F-16; r04, `Offer.php:479-496`
- **BR-18** A seller's eurocode text that matches nothing is kept on the offer but links nothing, silently; the result is only recorded in the submission payload. — F-16; r04, `OfferCreatedByUserAction.php:59-97`
- **BR-09** The submission action replaces **all** of the offer's eurocodes with the one it creates (`sync`). — F-10; r01, spot-checked `ViewSubmission.php:191-205`

- **BR-20** Publishing to WooCommerce requires a primary eurocode and type, producer and colour that are not Pending; an Otomoto advert also requires at least one car through the eurocodes. — r03, `Offer.php:259-340`

### Inferred or questionable

- **BR-19** In the same save, the WooCommerce push may carry the previous eurocode links, because links are written after the update event. — `inferred` from ordering, r04; no transaction wraps the offer save.

### Domain terms

- **Eurokod** — glass identifier; in V1 only a code with OEM numbers and vehicle fitment (r01).
- **Kody OEM** — "e.g. original and alternative" (form helper); a list per eurocode, plus a separate single `oem` on the offer (r01).
- **Kompatybilne pojazdy** — the eurocode's fitment list (r01).
- **Pojazd** — `brand | model | type | year`, free text; "type" is shown as "version" in WordPress (r01).
- **Rodzaj szyby** — glass type/position (seller choices PRZEDNIA, TYLNA, BOCZNA, SZYBA DACHOWA); a Woo product category (r02).
- **Producent** — maker of the glass piece; exported to WordPress under the meta key `brand` (r02) — not the car brand.
- **Kolor**, **Dodatkowe wyposażenie** (WP `accessories`) — glass dictionaries (r02).
- **Rok produkcji**, **Stan** (Nowa / Używana / Outlet), **Ocena** 1–5 — per-piece condition data on the offer (r02, r04).
- **Nie posiadam eurocodu / kodu OEM**, **Informacje o pojeździe** — the without-eurocode path (r04).
- **Oczekuje zatwierdzenia / Aktywna**, **Zatwierdź** — dictionary approval (r02).
- **Eurocode (industry)** — ARGIC identifier of a replacement glass for a vehicle model: position, tint, features, modification (r05). V1 "Eurokod" uses only the identifier.
- **OE / OEM number** — carmaker's part number; many-to-many with Eurocodes, superseded over time (r05).
- **Interchange** — industry term for parts usable in place of one another (Hollander full/partial); V1's "alternative eurocodes" are the nearest notion, but no public ARGIC list exists (r05).

## E. Functional workflows

### W-01 Assign eurocodes to an offer

- **Trigger / entry points:** offer create and edit forms (admin, seller); duplicate action; submission action (F-10).
- **Preconditions:** `without_eurocode` off.
- **Main flow (admin):** 1. search the eurocode register (code LIKE, 20 results), pick several in order; 2. may create a eurocode inline (permission); 3. on save the offer's links are replaced exactly, priority 0, 10, 20… by pick order.
- **Main flow (seller):** 1. type free text (suggestions from codes/OEMs ≥3 chars); 2. offer saved with the text; 3. after save, if no link exists yet, the text is normalised and matched to a eurocode by code or OEM; a match becomes the only link (priority 0); 4. W-02 runs.
- **State changes:** links (`eurocode_offer`); seller text in `offers.eurocode`.
- **Side effects:** submission for sellers ("Użytkownik utworzył ofertę") with the match result; notification to all admins; journal entry.
- **Error scenarios:** no match → nothing linked, no message (BR-18).
- **Alternative paths:** seller edit: empty or unmatched text detaches all links; a new match replaces all links, including alternatives (Q-08).
- **Evidence:** r04; `SelectEurocodesPartial.php:28-99`, `OfferCreatedByUserAction.php:59-97`, `OfferEditedByUserAction.php:236-334`; trace stopped at Woo/Otomoto sync (out of scope).

### W-02 Fill glass attributes from a sibling offer

- **Trigger / entry points:** admin changes the eurocode selection (live); after create (any user); admin edit when eurocodes change; seller edit after a new match.
- **Main flow:** 1. find another non-pending offer (trashed included, newest; admin live picks the most complete) sharing a eurocode; 2. copy colour and type if empty, equipment if none (admin live: overwrite form fields); 3. after create/seller edit also copy that offer's missing alternative eurocodes.
- **State changes:** offer colour/type/equipment; extra eurocode links.
- **Error scenarios / gaps:** values copied earlier are never reverted when the eurocode changes; admin edit looks up siblings by the legacy string columns, not by links.
- **Evidence:** r04; `OfferEurocodeRelationService.php:37-74`, `OfferCreatedByUserAction.php:99-167`, `EditOffer.php:224-271`.

### W-03 Offer without eurocode

- **Trigger:** toggle "Nie posiadam eurocodu / kodu OEM".
- **Main flow:** eurocode inputs hidden; "Informacje o pojeździe" shown; seller picks type and colour by hand; no link is written on create.
- **Alternative paths:** seller edit with toggle on clears the text and detaches all links; admin edit does not (Q-13).
- **Evidence:** r04; `OfferCreateForm.php:182-232`, `OfferEditedByUserAction.php:244-274`.

## F. Cross-module dependencies

- **X-01** Offers — holds the links to eurocodes (ordered), producer/colour/type, equipment; reads cars through eurocodes; lists, filters, view, duplicate and publish guards all read catalogue data (F-16–F-20, BR-20). Direction: Offers depends on the catalogue. r03, r04
- **X-02** Submissions — the seller's eurocode match result and pending dictionary values are reviewed there; an admin creates/assigns a eurocode and fitments from a submission (F-10). r01, r03
- **X-03** WooCommerce/WordPress (integration, later) — exports cars (`vehicles` posts), colours, producers, equipment (posts) and types (product categories); imports all of them plus offers; publish guard BR-20. The WordPress post types are not defined in V1's repo. r02, r03
- **X-04** Otomoto (dropped) — adverts read cars, eurocodes, OEM, producer, equipment; glass types and shipping methods are mapped to Otomoto options; its `Donor*` car catalogue is matched to cars by name only and never linked or written back. r02, r03
- **X-05** Users/Journal — the journal shows an offer's primary eurocode. r03
- **X-06** Audit — V1 logged only name/status of dictionaries and name/model of cars (V2 Audit decides its own scope). r01, r02

## G. Ambiguities, contradictions and questionable behaviour

### Q-01 Eurocode codes are normalised on some paths only — `defect?`

- **Evidence:** r01; admin form saves as typed (`EurocodeResource.php:30-34`), lookups uppercase and strip spaces (`OfferEurocodeRelationService.php:76-79`).
- **Interpretations:** a) oversight; b) admin entries were always typed clean.
- **Question for the user:** in V2, is a eurocode always stored normalised (uppercase, no spaces), and are OEM codes too?
- **Resolution:** open

### Q-02 Two answers to "which offers fit this car" — contradiction

- **Evidence:** r01; list count via eurocodes (`CarResource.php:46-57`), view page via `car_offer` (`ViewCar.php:46-49`).
- **Interpretations:** a) `car_offer` is legacy and fitment belongs on the eurocode; b) an offer may need cars of its own (e.g. glass without eurocode).
- **Question for the user:** is vehicle fitment only a property of the glass definition (eurocode), or can an offer carry its own vehicles?
- **Resolution:** open (also in domain grilling)

### Q-03 Car changes are probably not auto-pushed to WordPress — `defect?` (inferred)

- **Evidence:** r01; `Car::booted` overrides the trait's `booted` (`Car.php:56`, `IsOfferTaxonomyModel.php:14-31`).
- **Question for the user:** none now; WooCommerce discovery. Recorded for the integration.
- **Resolution:** open (WooCommerce)

### Q-04 Submission action drops the offer's other eurocodes — `defect?`

- **Evidence:** r01, spot-checked `ViewSubmission.php:203-205` (`sync`).
- **Interpretations:** a) intended: the submission corrects a wrong code; b) should add, not replace.
- **Question for the user:** when a eurocode is assigned from a submission, does it replace or add to the offer's codes? (Submissions/Offers boundary)
- **Resolution:** open

### Q-06 Is the seller wizard reachable? — contradiction (settled)

- **Evidence:** r02 described value proposals for type/colour/equipment through `UserOfferCreateForm`; r04 found that form referenced nowhere. Manager check: no reference in `app/`, `resources/`, `routes/`; the live create form has a create option only on producer (`OfferCreateForm.php:430`).
- **Resolution:** settled by spot-check — wizard unreachable; live proposals exist for producer only. Its `oem`, `vim` and car steps are dead too.

### Q-07 Legacy glass columns on the offer — `decide`

- **Evidence:** r02, r04; `eurocode`, `alt_eurocodes`, `oem`, `vim` on `offers` are written only by the Woo import, a command or the seller's eurocode text; displays and exports derive from the links.
- **Question for the user:** in V2, are eurocodes/OEM only reachable through the glass definition, and is VIN a thing at all (nothing in V1 uses it)?
- **Resolution:** open (domain grilling)

### Q-08 Seller eurocode entry — `decide`

- **Evidence:** r04; free text, silent no-match (BR-18), a new match drops alternatives.
- **Question for the user:** should a seller pick from the register (with "not found → request"), keep free text matched later, or start from a vehicle/VIN?
- **Resolution:** open

### Q-09 Glass attributes copied between offers — `decide`

- **Evidence:** r01 BR-06, r04 W-02; four copying paths, inconsistent sibling lookup, never reverted.
- **Interpretations:** a) the copy is a workaround for a missing glass definition per eurocode; b) attributes really vary per piece and the copy is only a default.
- **Question for the user:** which of colour, type, equipment (and producer) are fixed by the eurocode and which vary per piece? Core of the Glass vs Offer question.
- **Resolution:** open (domain grilling)

### Q-10 Deleting a dictionary value in use — `decide`

- **Evidence:** r02; no FK, no guard; offers keep dangling ids and show an empty value.
- **Question for the user:** block delete when used, merge into another value, or deactivate?
- **Resolution:** open

### Q-11 `OfferExtras` — dead (settled fact)

- **Evidence:** r02; model and table only, no references.
- **Resolution:** exclude unless the user disagrees (to confirm in grilling).

### Q-12 Who owns shipping methods — `ambiguous`

- **Evidence:** r02; a priced list with a free-text price; default on glass type; only Otomoto (dropped) reads it.
- **Question for the user:** does shipping belong to the glass catalogue (by glass type), to Offers/sales, or is it dropped with Otomoto?
- **Resolution:** open

### Q-13 Admin edit with "without eurocode" keeps old links — `defect?` (inferred)

- **Evidence:** r04; seller path detaches, admin path does not (hidden field not saved).
- **Resolution:** open (V2 rule follows from Q-08)

### Q-14 Glass type mixes position and shop category — `ambiguous`

- **Evidence:** r02, r04; seller options are positions (front, rear, side, roof) hard-coded by name; the type is a Woo product category and defaults shipping.
- **Question for the user:** is "glass type" the glass position, a shop category, or both?
- **Resolution:** open

### Q-15 Search promises OEM but searches only eurocodes — `defect?`

- **Evidence:** r03; "MyOffers" placeholder "eurocode, nr OEM lub SKU" (`MyOffers.php:63`), only `eurocodes.code` and SKU are searchable.
- **Question for the user:** should finding offers by OEM (and by vehicle) be a V2 requirement?
- **Resolution:** open

### Q-16 Imported OEMs go nowhere visible — `defect?`

- **Evidence:** r03, r01; `wordpress:sync-oems` writes `offers.oem`; everything reads `eurocodes.oem_codes`.
- **Resolution:** open (data migration: whether `offers.oem` values are folded into eurocodes)

### Q-05 Offer is not touched when its eurocode links change — `defect?`

- **Evidence:** r01; `EurocodeOffer::$touches` exists but `->using()` is commented out (`Offer.php:420-427`).
- **Question for the user:** none; affects change history/sync, settle in Offers.
- **Resolution:** open

## H. Missing information and gaps

| ID | What is unknown | Kind | Why | Resolution |
|---|---|---|---|---|
| G-01 | Who may manage cars and eurocodes (policies, Shield) | needs-business | r03: Shield permissions per resource (`CarPolicy`, duplicate taxonomy policies); role grants live in unread Shield seeders. V2 uses its own Authorizer (ADR 0004), so the question becomes who should manage the catalogue | open → grilling |
| G-02 | Were the backfill/OEM commands run in production; is the eurocode table curated by hand since | needs-business | static reading cannot tell | open |
| G-03 | Does `cars` have a `status` column (used by `Car::canBeSync`) | not-found | manager check: no column, no later migration; the status check never blocks | resolved (integration detail) |
| G-04 | Otomoto sync tab writes car ids (`OtomotoSynchronizationTab.php:145-160`) — fitment? | — | r03: it picks one of the offer's eurocode cars for the advert (stored in advert meta), not a fitment | resolved: not fitment |
| G-05 | Does the eurocode row edit work with the edit route commented out | needs-runtime | Filament modal fallback expected | open (low) |
| G-06 | Which roles hold the dictionary permissions (`offer::taxonomies::*`) | needs-business | merged into G-01 | G-01 |
| G-07 | Which page/form sellers actually use ("MyOffers"?) | — | r03: `OfferCreateForm` is referenced only by `OfferResource`; "MyOffers" is a list page with its own filters | resolved (`inferred` from references) |
| G-08 | Why dictionary and car names may not contain `_` | needs-business | no reason in code | open |
| G-09 | Row counts and real data shape (how many eurocodes, cars, values; how many offers without eurocode or links) | needs-runtime | production dump supplied by the user 2026-11-09, loaded as `allglass_v1_prod` (last offer 2026-10-01; offers 2046, eurocodes 1817, cars 1188, eurocode_offer 2195, car_eurocode 2827, car_offer 1531) | partly resolved — data-shape queries still to run |

## I. Potential improvements

- **IMP-01** Normalise eurocode and OEM codes on every write — current: admin form saves as typed (Q-01) → one normalisation rule (uppercase, no spaces), codes as opaque strings up to 17 characters (r05); value: lookups and uniqueness work; decision: open
- **IMP-02** One path from an offer to its vehicles — current: `car_offer` plus fitment through eurocodes, two counts (Q-02) → vehicles only through the glass definition's fitment; value: one answer; decision: open
- **IMP-03** Guard deleting a dictionary value in use — current: dangling ids (Q-10) → block, merge or deactivate; decision: open
- **IMP-04** Seller picks the glass from the register — current: free text matched after save, silent no-match (Q-08, BR-18) → pick or search by eurocode/OEM, with an explicit "not found" request to an admin; decision: open
- **IMP-05** Find offers by OEM and by vehicle — current: placeholder promises OEM, searches only codes (Q-15) → search by eurocode, OEM, vehicle; decision: open
- **IMP-06** Integration keys out of the catalogue — current: every dictionary knows its WordPress endpoint and meta key (r02) → mappings live in the integration (ADR 0007 pattern); decision: open

## J. Additional functionality to implement

### ADD-01 Glass definition per eurocode

- **Description:** a catalogue record of a glass (keyed by eurocode, or an internal id with eurocode/OEM as references) holding position/type, tint, features/equipment, OEM codes and vehicle fitment; offers point to it instead of copying attributes from sibling offers.
- **Value:** removes W-02 copying and the duplicated offer columns; one place to fix data; basis for presets.
- **Kind:** extends F-02, F-06, F-17
- **Dependencies:** domain grilling (Glass vs Offer), Q-07, Q-09, Q-14
- **Open questions:** key (eurocode vs internal id); glass without eurocode; who creates definitions.
- **Source:** user (2026-11-08), supported by r04, r05
- **Discussed:** no · **Decision:** open

### ADD-02 Interchange between glass definitions

- **Description:** explicit "can replace" links between definitions (full/partial), replacing V1's alternative eurocodes on the offer.
- **Value:** offer search across equivalent glass. **Kind:** extends F-04. **Dependencies:** ADD-01.
- **Open questions:** symmetric groups or directed links; who maintains them (no public list, r05).
- **Source:** discovery (r05) · **Discussed:** no · **Decision:** open

### ADD-03 Structured vehicle catalogue

- **Description:** make → model → generation/years (and body) as dictionaries instead of free text; optional later mapping to a reference tree (TecDoc KType, licensed).
- **Value:** consistent fitment and search; V1 brand table was planned and never built (r01).
- **Kind:** extends F-01. **Source:** discovery (r01, r05) · **Discussed:** no · **Decision:** open

### ADD-04 VIN lookup

- **Description:** find candidate glass definitions from a VIN through a paid build-data provider; results are candidates, confirmed by hand.
- **Value:** the user's VIN idea; V1 has a dead `vim` column only. **Kind:** entirely new.
- **Dependencies:** ADD-01, ADD-03, a provider and its cost (r05: about £2.20 per lookup for one provider).
- **Source:** user, r05 · **Discussed:** no · **Decision:** open (likely later)

### ADD-05 Features as a closed multi-valued dictionary

- **Description:** equipment/features as a fixed list (antenna, camera, heated, sensor, HUD, VIN window, encapsulation…) optionally mapped to Eurocode letters, instead of free admin-created names.
- **Value:** consistent data; imports/exports by code. **Kind:** extends F-12. **Source:** discovery (r05) · **Discussed:** no · **Decision:** open

## K. Investigation log

| Report | Mission | Scope / question | Status | Key results | Limits | Follow-ups | Checked |
|---|---|---|---|---|---|---|---|
| r01 | local | vehicles + eurocodes anchors | returned, good | F-01–F-11, BR-01–09, Q-01–05; eurocode holds no glass params; two car links | trait file read to line 60 only; no permissions | G-01, G-03, G-04 → r03 | spot: BR-04, BR-07, BR-09 |
| r02 | local | glass attributes (colour, producer, type, equipment, extras) + Offer's glass columns | returned, good | F-12–F-15, BR-10–16; four dictionaries + approval; Extras dead; shipping only for Otomoto | producer pages assumed = colour; roles unknown; described the dead wizard as live (Q-06) | G-06 → r03 | spot: Q-06 (wizard) |
| r03 | cross-repo | vocabulary across V1 + entry-point census | returned, good | 46-row census; F-19–21, BR-20, X-01–06, Q-15–16; import writes no links; Donor* unlinked | role grants unread; no DB counts | G-09 | spot: F-21 (import writes strings only), G-03 |
| r04 | flow | assigning eurocode/vehicles/attributes when an offer is created or edited | returned, good | W-01–03, F-16–18, BR-17–19; links authoritative; attributes copied from sibling offers | hidden-field and Woo-order claims inferred | G-07 → r03 | spot: Q-06, BR-09 path (`OfferEditedByUserAction.php:105`) |
| r05 | research | what a eurocode encodes; industry split of glass definition vs stock item vs listing | returned, good | Eurocode structure and limits; three-layer pattern; IMP-01, ADD-01–05 | ARGIC tables member-only; decoding from distributor pages | — | sources cited (39); not a V1 claim, no code spot-check |

## L. Completeness assessment

- **Exit criteria:**
  1 ✓ every census row (r03, 46) maps to F-01–F-21, is out of scope (Otomoto rows 28–32, 39–40; Woo rows 33–38 recorded as F-15/F-21/X-03) or dead (13, 14, 18, 44–46).
  2 ✓ every F- has entry points, tags and confidence; W-01–03 traced; the rest give a reason (CRUD, single action, integration).
  3 ✓ matched-not-examined files are integration/Otomoto code (out of scope), Shield seeders (→ G-01) or other missions' anchors.
  4 ✓ leads followed (r01 leads → r03) or out of scope (Offers: `getMinimumQuantity`, status lifecycle; Otomoto mappings).
  5 ✓ the one contradiction (Q-06) settled by spot-check.
  6 ✓ high-impact rules have a second source: BR-04, BR-07, BR-09 spot-checked; BR-06, BR-11, BR-17, BR-18, BR-20 confirmed by two reports; F-21 spot-checked.
  7 accepted exception: one wave only. r03 added F-19–F-21, but they are display and integration entry points found by the whole-repo census, not new functional areas of the catalogue; the census is the completeness denominator and is closed. Remaining V1 doubts are runtime-only (G-05, Q-03, Q-13, `EditOffer::beforeCreate`) and do not change V2 behaviour.
  8 ✓ sections I and J filled.
- **Mapped:** 21 F-, 20 BR-, 3 W-, 6 X-, 16 Q- (2 settled), 9 G- (3 resolved), 6 IMP-, 5 ADD-.
- **Verified:** spot-checks on BR-04, BR-07, BR-09, Q-06, F-21, G-03.
- **Still uncertain:** G-01 (who manages the catalogue), G-02 (commands run in production), G-08 (no `_` in names), G-09 (real data volumes); Q-03, Q-13 (runtime).
- **Weak coverage:** real data shape (no DB); WordPress post types (not in V1 repo; WooCommerce discovery).
- **Ready because:** the census is closed, every operation that writes catalogue data is traced or classified, and the open items are decisions, not missing facts.

## M. Decision log
