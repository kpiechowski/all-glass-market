# Catalogue — discovery

<!-- Working document for the module-discovery skill. Only the manager writes it.
     Item ids are stable and never reused. Every finding cites a report (rNN) and its V1 evidence.
     Tags: confirmed · observed · inferred · ambiguous · unknown · defect? · decide -->

## A. Metadata

- **Status:** spec-written
- **Next actions:** spec written and audited (`.scratch/catalogue/functional-spec.md`, r06 applied); data migration written (`.scratch/data-migration/catalogue/spec.md`, `ticket.md`, `review-list.md`). Waiting for the user's acceptance and commit. Next: cut Catalogue issues and deliver (D-66: Offers grilling postponed until after Catalogue delivery).
- **Started:** 2026-11-08 · **Last updated:** 2026-11-11
- **Inputs (D-28):** V1 has no catalogue, so this module gets no discovery of its own. Its inputs are this discovery (written as "Vehicles"), the Offers discovery `.scratch/offers/discovery/discovery.md` and the domain model `.scratch/domain/glass-offer-model.md` (D-01–D-28, GLOSSARY, ADR 0008–0010).
- **V1:** `/home/kp/laravel/projekty/AllGlass/` @ `511ce3b` (dirty: `docker-compose.yml` modified, `SECURITY_INCIDENT_2026-10.md` untracked; neither touches this module)
- **V2 state:** module absent. The module is **Catalogue** (domain D-23); the folder was renamed from `vehicles` on 2026-11-11.
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
- **Resolution:** partly D-03: codes are unique across the catalogue and stored normalised (uppercase, no spaces, ≤ 17 characters). The details for Eurocodes and OEM codes stay open → Catalogue grilling (deferred list).

### Q-02 Two answers to "which offers fit this car" — contradiction

- **Evidence:** r01; list count via eurocodes (`CarResource.php:46-57`), view page via `car_offer` (`ViewCar.php:46-49`).
- **Interpretations:** a) `car_offer` is legacy and fitment belongs on the eurocode; b) an offer may need cars of its own (e.g. glass without eurocode).
- **Question for the user:** is vehicle fitment only a property of the glass definition (eurocode), or can an offer carry its own vehicles?
- **Resolution:** D-11, D-15 — fitment is a property of the GlassModel only; an Offer has no vehicles of its own; `car_offer` is dropped.

### Q-03 Car changes are probably not auto-pushed to WordPress — `defect?` (inferred)

- **Evidence:** r01; `Car::booted` overrides the trait's `booted` (`Car.php:56`, `IsOfferTaxonomyModel.php:14-31`).
- **Question for the user:** none now; WooCommerce discovery. Recorded for the integration.
- **Resolution:** open (WooCommerce). D-26: the WordPress push and its keys belong to the integration.

### Q-04 Submission action drops the offer's other eurocodes — `defect?`

- **Evidence:** r01, spot-checked `ViewSubmission.php:203-205` (`sync`).
- **Interpretations:** a) intended: the submission corrects a wrong code; b) should add, not replace.
- **Question for the user:** when a eurocode is assigned from a submission, does it replace or add to the offer's codes? (Submissions/Offers boundary)
- **Resolution:** D-20, D-03 — there is no Submission and no ticket action; V1 alternative eurocodes become Eurocodes of one GlassModel and an Offer refers to that GlassModel.

### Q-06 Is the seller wizard reachable? — contradiction (settled)

- **Evidence:** r02 described value proposals for type/colour/equipment through `UserOfferCreateForm`; r04 found that form referenced nowhere. Manager check: no reference in `app/`, `resources/`, `routes/`; the live create form has a create option only on producer (`OfferCreateForm.php:430`).
- **Resolution:** settled by spot-check — wizard unreachable; live proposals exist for producer only. Its `oem`, `vim` and car steps are dead too.

### Q-07 Legacy glass columns on the offer — `decide`

- **Evidence:** r02, r04; `eurocode`, `alt_eurocodes`, `oem`, `vim` on `offers` are written only by the Woo import, a command or the seller's eurocode text; displays and exports derive from the links.
- **Question for the user:** in V2, are eurocodes/OEM only reachable through the glass definition, and is VIN a thing at all (nothing in V1 uses it)?
- **Resolution:** D-03, D-15, D-16 — eurocodes and OEM codes are reached only through the GlassModel; `offers.vim` is dropped and VIN is only a search input (later) or part of an identification hint.

### Q-08 Seller eurocode entry — `decide`

- **Evidence:** r04; free text, silent no-match (BR-18), a new match drops alternatives.
- **Question for the user:** should a seller pick from the register (with "not found → request"), keep free text matched later, or start from a vehicle/VIN?
- **Resolution:** D-17, D-10 — the seller picks a GlassModel from the catalogue (eurocode, OEM or vehicle); if nothing fits, they leave an identification hint. No free text matched after save.

### Q-09 Glass attributes copied between offers — `decide`

- **Evidence:** r01 BR-06, r04 W-02; four copying paths, inconsistent sibling lookup, never reverted.
- **Interpretations:** a) the copy is a workaround for a missing glass definition per eurocode; b) attributes really vary per piece and the copy is only a default.
- **Question for the user:** which of colour, type, equipment (and producer) are fixed by the eurocode and which vary per piece? Core of the Glass vs Offer question.
- **Resolution:** D-01, D-07, D-08, D-09 — position, colour and equipment are on the GlassModel; producer, year and condition are on the Offer; nothing is copied and nothing is overridden.

### Q-10 Deleting a dictionary value in use — `decide`

- **Evidence:** r02; no FK, no guard; offers keep dangling ids and show an empty value.
- **Question for the user:** block delete when used, merge into another value, or deactivate?
- **Resolution:** open — deferred to the Catalogue grilling (deleting or merging a value in use; D-24, D-25).

### Q-11 `OfferExtras` — dead (settled fact)

- **Evidence:** r02; model and table only, no references.
- **Resolution:** D-25 — excluded: equipment is one list, `OfferExtras` is dropped.

### Q-12 Who owns shipping methods — `ambiguous`

- **Evidence:** r02; a priced list with a free-text price; default on glass type; only Otomoto (dropped) reads it.
- **Question for the user:** does shipping belong to the glass catalogue (by glass type), to Offers/sales, or is it dropped with Otomoto?
- **Resolution:** open — deferred. D-08 keeps shipping out of the model for now; the user asks the panel admin first.

### Q-13 Admin edit with "without eurocode" keeps old links — `defect?` (inferred)

- **Evidence:** r04; seller path detaches, admin path does not (hidden field not saved).
- **Resolution:** D-10, D-17 — an Offer either refers to a GlassModel or carries a hint; the V1 toggle and its two diverging paths go.

### Q-14 Glass type mixes position and shop category — `ambiguous`

- **Evidence:** r02, r04; seller options are positions (front, rear, side, roof) hard-coded by name; the type is a Woo product category and defaults shipping.
- **Question for the user:** is "glass type" the glass position, a shop category, or both?
- **Resolution:** D-07, D-25, D-27 — "Rodzaj szyby" is the glass position, an admin-edited list on the GlassModel (not a code enum); a shop category is an integration mapping (D-26); accessories stay as position values for now.

### Q-15 Search promises OEM but searches only eurocodes — `defect?`

- **Evidence:** r03; "MyOffers" placeholder "eurocode, nr OEM lub SKU" (`MyOffers.php:63`), only `eurocodes.code` and SKU are searchable.
- **Question for the user:** should finding offers by OEM (and by vehicle) be a V2 requirement?
- **Resolution:** partly D-14, D-24 — the catalogue search by eurocode, OEM or vehicle returns candidate GlassModels. Searching Offer lists with it → Offers grilling.

### Q-16 Imported OEMs go nowhere visible — `defect?`

- **Evidence:** r03, r01; `wordpress:sync-oems` writes `offers.oem`; everything reads `eurocodes.oem_codes`.
- **Resolution:** D-14 — `offers.oem` values are folded into GlassModel OEM codes by the data migration (Catalogue Phase 9).

### Q-05 Offer is not touched when its eurocode links change — `defect?`

- **Evidence:** r01; `EurocodeOffer::$touches` exists but `->using()` is commented out (`Offer.php:420-427`).
- **Question for the user:** none; affects change history/sync, settle in Offers.
- **Resolution:** D-24 — an Offer stores only the GlassModel id; a GlassModel change is a domain event, so the V1 link-touch problem does not arise.

## H. Missing information and gaps

| ID | What is unknown | Kind | Why | Resolution |
|---|---|---|---|---|
| G-01 | Who may manage cars and eurocodes (policies, Shield) | needs-business | r03: Shield permissions per resource (`CarPolicy`, duplicate taxonomy policies); role grants live in unread Shield seeders. V2 uses its own Authorizer (ADR 0004), so the question becomes who should manage the catalogue | partly D-18: only admins, never sellers; which admins exactly → Catalogue grilling (deferred) |
| G-02 | Were the backfill/OEM commands run in production; is the eurocode table curated by hand since | needs-business | static reading cannot tell | D-13 — the migration works from the production data as it stands (r06), joining eurocodes that stood on one offer; whether the commands ran no longer matters |
| G-03 | Does `cars` have a `status` column (used by `Car::canBeSync`) | not-found | manager check: no column, no later migration; the status check never blocks | resolved (integration detail) |
| G-04 | Otomoto sync tab writes car ids (`OtomotoSynchronizationTab.php:145-160`) — fitment? | — | r03: it picks one of the offer's eurocode cars for the advert (stored in advert meta), not a fitment | resolved: not fitment |
| G-05 | Does the eurocode row edit work with the edit route commented out | needs-runtime | Filament modal fallback expected | moot — the eurocode screen is replaced by Glass models (D-30) |
| G-06 | Which roles hold the dictionary permissions (`offer::taxonomies::*`) | needs-business | merged into G-01 | G-01 |
| G-07 | Which page/form sellers actually use ("MyOffers"?) | — | r03: `OfferCreateForm` is referenced only by `OfferResource`; "MyOffers" is a list page with its own filters | resolved (`inferred` from references) |
| G-08 | Why dictionary and car names may not contain `_` | needs-business | no reason in code | open |
| G-09 | Row counts and real data shape (how many eurocodes, cars, values; how many offers without eurocode or links) | needs-runtime | production dump `allglass_v1_prod` (2026-11-09) | resolved — `.scratch/offers/discovery/reports/r06-data-shape.md`: 1817 eurocodes, no duplicates after normalisation (4 odd codes incl. `test123456`), 45 without offers; all dictionary rows Aktywna (approval workflow unused); `car_offer` covers 790 offers, identical to eurocode fitment for 722, never disjoint; 37 offers without links, only 1 flagged; within a eurocode type 99.6%, colour 97.5%, equipment 93.3% constant, producer 46.2% |

## I. Potential improvements

- **IMP-01** Normalise eurocode and OEM codes on every write — current: admin form saves as typed (Q-01) → one normalisation rule (uppercase, no spaces), codes as opaque strings up to 17 characters (r05); value: lookups and uniqueness work; decision: D-03 (stored normalised); the details → Catalogue grilling
- **IMP-02** One path from an offer to its vehicles — current: `car_offer` plus fitment through eurocodes, two counts (Q-02) → vehicles only through the glass definition's fitment; value: one answer; decision: D-15 — accepted
- **IMP-03** Guard deleting a dictionary value in use — current: dangling ids (Q-10) → block, merge or deactivate; decision: D-44 — delete only when unused, else withdraw
- **IMP-04** Seller picks the glass from the register — current: free text matched after save, silent no-match (Q-08, BR-18) → pick or search by eurocode/OEM, with an explicit "not found" request to an admin; decision: D-17 — accepted, with an identification hint instead of a request to an admin
- **IMP-05** Find offers by OEM and by vehicle — current: placeholder promises OEM, searches only codes (Q-15) → search by eurocode, OEM, vehicle; decision: partly D-14, D-24 (catalogue search returns candidates); Offer lists → Offers grilling
- **IMP-06** Integration keys out of the catalogue — current: every dictionary knows its WordPress endpoint and meta key (r02) → mappings live in the integration (ADR 0007 pattern); decision: D-26 — accepted

## J. Additional functionality to implement

### ADD-01 Glass definition per eurocode

- **Description:** a catalogue record of a glass (keyed by eurocode, or an internal id with eurocode/OEM as references) holding position/type, tint, features/equipment, OEM codes and vehicle fitment; offers point to it instead of copying attributes from sibling offers.
- **Value:** removes W-02 copying and the duplicated offer columns; one place to fix data; basis for presets.
- **Kind:** extends F-02, F-06, F-17
- **Dependencies:** domain grilling (Glass vs Offer), Q-07, Q-09, Q-14
- **Open questions:** key (eurocode vs internal id); glass without eurocode; who creates definitions.
- **Source:** user (2026-11-08), supported by r04, r05
- **Discussed:** yes (domain grilling) · **Decision:** D-01, D-03, D-04, D-07 — accepted as the **GlassModel**: an internal entity with one or more Eurocodes (one main), OEM codes as lookup keys, fitment, position, colour, equipment; only admins create it (D-18).

### ADD-02 Interchange between glass definitions

- **Description:** explicit "can replace" links between definitions (full/partial), replacing V1's alternative eurocodes on the offer.
- **Value:** offer search across equivalent glass. **Kind:** extends F-04. **Dependencies:** ADD-01.
- **Open questions:** symmetric groups or directed links; who maintains them (no public list, r05).
- **Source:** discovery (r05) · **Discussed:** yes (domain grilling) · **Decision:** D-12 — not built now; one-way "can replace" is a known future relation between GlassModels, never an extra Eurocode. V1 alternatives become Eurocodes of one GlassModel (D-03).

### ADD-03 Structured vehicle catalogue

- **Description:** make → model → generation/years (and body) as dictionaries instead of free text; optional later mapping to a reference tree (TecDoc KType, licensed).
- **Value:** consistent fitment and search; V1 brand table was planned and never built (r01).
- **Kind:** extends F-01. **Source:** discovery (r01, r05) · **Discussed:** yes · **Decision:** D-31, D-34 — structured vehicle fields without a make → model → generation tree; the tree stays a later option (D-23).

### ADD-04 VIN lookup

- **Description:** find candidate glass definitions from a VIN through a paid build-data provider; results are candidates, confirmed by hand.
- **Value:** the user's VIN idea; V1 has a dead `vim` column only. **Kind:** entirely new.
- **Dependencies:** ADD-01, ADD-03, a provider and its cost (r05: about £2.20 per lookup for one provider).
- **Source:** user, r05 · **Discussed:** yes (domain grilling) · **Decision:** D-16 — later: VIN is a search input through a paid provider, never a GlassModel attribute. Not in the first spec (D-58).

### ADD-06 Glass model thumbnail

- **Description:** the first column of the Glass model list shows a generic SVG of a glass, whose shape follows the model's position (front, side, rear…) and whose fill follows its colour (tint). Catalogue settings hold three mappings: position → shape (mirrored), colour → fill, equipment → pictogram (D-54–D-56).
- **Value:** the catalogue table reads at a glance. Cost: the SVG mechanism and the two mappings.
- **Kind:** extends F-02 (the Glass model list, D-30). **Dependencies:** D-25 (position and colour lists), D-30.
- **Source:** user (2026-11-11, round 1 Q2; equipment pictograms added in Phase 7) · **Discussed:** yes · **Decision:** D-54–D-57 — accepted.

### ADD-07 Merging Glass models

- **Description:** pick a surviving model; Eurocodes, OEM codes and vehicles are joined; parameter differences shown before confirming; Offers re-pointed through an event.
- **Value:** fixes duplicate models with many Offers in one step. **Kind:** new. **Dependencies:** D-24, D-36.
- **Source:** manager, round 2 Q4 · **Discussed:** yes · **Decision:** D-40 — not built; add if duplicates turn out frequent.

### ADD-05 Features as a closed multi-valued dictionary

- **Description:** equipment/features as a fixed list (antenna, camera, heated, sensor, HUD, VIN window, encapsulation…) optionally mapped to Eurocode letters, instead of free admin-created names.
- **Value:** consistent data; imports/exports by code. **Kind:** extends F-12. **Source:** discovery (r05) · **Discussed:** yes (domain grilling) · **Decision:** open — D-25 keeps equipment one admin-edited list and names a closed ARGIC-lettered list as a later option (Phase 7).

## K. Investigation log

| Report | Mission | Scope / question | Status | Key results | Limits | Follow-ups | Checked |
|---|---|---|---|---|---|---|---|
| r01 | local | vehicles + eurocodes anchors | returned, good | F-01–F-11, BR-01–09, Q-01–05; eurocode holds no glass params; two car links | trait file read to line 60 only; no permissions | G-01, G-03, G-04 → r03 | spot: BR-04, BR-07, BR-09 |
| r02 | local | glass attributes (colour, producer, type, equipment, extras) + Offer's glass columns | returned, good | F-12–F-15, BR-10–16; four dictionaries + approval; Extras dead; shipping only for Otomoto | producer pages assumed = colour; roles unknown; described the dead wizard as live (Q-06) | G-06 → r03 | spot: Q-06 (wizard) |
| r03 | cross-repo | vocabulary across V1 + entry-point census | returned, good | 46-row census; F-19–21, BR-20, X-01–06, Q-15–16; import writes no links; Donor* unlinked | role grants unread; no DB counts | G-09 | spot: F-21 (import writes strings only), G-03 |
| r04 | flow | assigning eurocode/vehicles/attributes when an offer is created or edited | returned, good | W-01–03, F-16–18, BR-17–19; links authoritative; attributes copied from sibling offers | hidden-field and Woo-order claims inferred | G-07 → r03 | spot: Q-06, BR-09 path (`OfferEditedByUserAction.php:105`) |
| r06 | spec-audit | functional-spec.md vs C, J, M | returned, good | ~45 findings: untraced pre-save offer warning (user had rejected it), seller vs withdrawn contradiction, dictionary status wording, count error, vague rules | did not read client file | all applied to the spec 2026-11-11 except Offers-side D-10/D-19 (→ Offers spec) and search limits (delivery) | n/a |
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

<!-- Class: preserve | correct | new | exclude | pending -->

D-01–D-28 are the **domain decisions** of `.scratch/domain/glass-offer-model.md` (2026-11-09), carried in on 2026-11-11 with the same ids; only those touching this module are listed. Reasons are in the domain model. They are settled ground for the module grilling, which continues at **D-29**.

- **D-01** (domain, 2026-11-09) re ADD-01, Q-09, W-02, F-06, F-17, BR-06 — The catalogue entry is a **GlassModel**; an Offer refers to it and reads glass parameters from it. No copying from sibling offers. Class: correct.
- **D-03** (domain, 2026-11-09) re BR-03, BR-05, F-04, F-19, Q-01, IMP-01, ADD-02 — A Eurocode belongs to exactly one GlassModel; a GlassModel has one or more. Codes are unique across the catalogue and stored normalised (uppercase, no spaces, ≤ 17 characters). OEM codes are lookup keys on the GlassModel. V1 per-offer alternative eurocodes become Eurocodes of the GlassModel. Class: correct.
- **D-04** (domain, 2026-11-09) re ADD-01, BR-05, Q-08 — A GlassModel has at least one Eurocode, exactly one of them main; the main one names it in the panel. No GlassModel from OEM codes alone. Class: new.
- **D-05** (domain, 2026-11-09) re F-21, X-03, Q-02 — The panel publishes Adverts and pulls nothing back from a channel. The runtime import from WordPress does not carry over; V1 data comes through the data migration. Class: exclude.
- **D-07** (domain, 2026-11-09) re Q-09, Q-14, IMP-06, BR-12, F-12 — The GlassModel holds position, colour, equipment, vehicle fitment and OEM codes. "Rodzaj szyby" becomes the position; a shop category is an integration mapping. Class: correct.
- **D-08** (domain, 2026-11-09) re BR-16, Q-12 — Producer, production year, condition, rating, photos, price, seller and status are on the Offer. Shipping is out of the model for now. Class: correct.
- **D-09** (domain, 2026-11-09) re Q-09, W-02 — An Offer never overrides a GlassModel parameter; another tint, coating or feature is another GlassModel. The V1 conflicts go to the admin's review list before migration. Class: correct.
- **D-10** (domain, 2026-11-09) re W-03, F-18, BR-18, Q-08, Q-13 — An Offer without a GlassModel has no glass parameters, only the seller's identification hint. Class: correct.
- **D-11** (domain, 2026-11-09) re F-03, BR-04, Q-02 — Vehicle fitment hangs on the GlassModel (the union of its Eurocodes' V1 fitment). No reason or source on a Eurocode for now. Class: correct.
- **D-12** (domain, 2026-11-09) re ADD-02 — One-way "can replace" between GlassModels is not built now; it is a known future relation, never an extra Eurocode. Class: exclude.
- **D-13** (domain, 2026-11-09) re BR-05, BR-08, F-07, G-02 — Migration: Eurocodes that stood together on one V1 offer join one GlassModel, transitively. Suffix-different pairs and groups with conflicting parameters go to the admin's review list. Class: correct.
- **D-14** (domain, 2026-11-09) re BR-03, F-05, Q-15, Q-16, IMP-05 — An OEM code is a lookup key that may belong to several GlassModels; a lookup by OEM returns candidates. Class: correct.
- **D-15** (domain, 2026-11-09) re Q-02, IMP-02, BR-07, BR-17, ADD-03 — Vehicles reach an Offer only through its GlassModel; `car_offer` is dropped and folded in by the migration or the admin. The vehicle catalogue's structure → Catalogue grilling. Class: correct.
- **D-16** (domain, 2026-11-09) re Q-07, ADD-04 — VIN is never a GlassModel attribute. It is a search input later (paid provider) and may be part of an identification hint. `offers.vim` is dropped. Class: exclude.
- **D-17** (domain, 2026-11-09) re W-01, F-05, F-16, Q-08, IMP-04, BR-18 — A seller picks a GlassModel from the catalogue by eurocode, OEM or vehicle and describes only the piece; if nothing fits, they leave a hint. No silent matching after save. Sellers see the catalogue, never other sellers' offers. Class: correct.
- **D-18** (domain, 2026-11-09) re G-01, BR-10, BR-11, F-13 — Only admins maintain the catalogue (GlassModels, Eurocodes, OEM codes, fitment). Sellers never add to it, directly or by proposals. Class: correct.
- **D-19** (domain, 2026-11-09) re F-10 — An Offer cannot be approved without a GlassModel; the admin finds or creates it from the hint while approving. Class: correct.
- **D-20** (domain, 2026-11-09) re F-10, X-02, Q-04 — There is no Submission concept; the ticket action "Dodaj eurokod" and the dictionary review on the ticket go. Class: exclude.
- **D-23** (domain, 2026-11-09) re B, scope, ADD-03, ADD-04 — One module, **Catalogue**: GlassModel, Eurocode, OEM codes, fitment, vehicles and the glass dictionaries. Vehicles split out only when they get a life of their own. Class: correct.
- **D-24** (domain, 2026-11-09) re X-01, Q-05 — Contract: an Offer stores only the GlassModel id; Offers reads the model summary and searches candidates through Catalogue's `ModuleApi`; Catalogue knows nothing of Offers. A GlassModel change is a domain event. Deleting a model in use → Catalogue grilling. Class: new.
- **D-25** (domain, 2026-11-09) re F-12, F-13, BR-10–BR-13, Q-10, Q-11, Q-14, G-09, ADD-05 — Position, colour, equipment and producer are admin-edited lists in Catalogue: no approval workflow, no seller proposals, position not a code enum, equipment one list (`OfferExtras` dropped). Value clean-up → Catalogue grilling. Class: correct.
- **D-26** (domain, 2026-11-09) re F-11, F-15, IMP-06, Q-03, Q-14, X-03 — Channel keys and mappings live in the integration; catalogue entries do not know channels. An entry's integration status on a tab → Catalogue grilling. Class: correct.
- **D-27** (domain, 2026-11-09) re Q-14 — Accessories (seal, moulding, frame) stay GlassModels whose position is an accessory value, as in V1. A separate Accessory model is ADD-A01 in the domain model, waiting for the client. Class: preserve.
- **D-28** (domain, 2026-11-09) re process — Order: Catalogue grilling → spec → Offers grilling → spec → Catalogue delivery → Offers delivery. No new discovery for Catalogue. Class: n/a (process).

### Module grilling (from 2026-11-11)

Round 1 (2026-11-11) — inventory. Facts (prod, aggregate): 1188 vehicles, 54 brands, 507 models, 68 body texts; `year` mixes generation and years (99% parse as a year range); 1165 vehicle names end with the 4-digit ARGIC vehicle block, which equals the eurocode prefix in 98% of fitments; the client searches by that name and year text.

- **D-29** (2026-11-11) re F-04, F-06, F-07, F-08, F-09, F-10, F-11, F-13, F-15, F-16, F-17, F-18, F-20, F-21 — Not in Catalogue: F-04 (an Offer stores only the Glass model id, D-24), F-06/F-17 (D-01), F-07/F-08 (replaced by the data-migration spec), F-09/F-21 (D-05), F-10 (D-20; replaced by the Approval workflow), F-13 (D-18) are dropped; F-11/F-15 belong to the integration (D-26); F-16/F-18/F-20 belong to Offers, and Catalogue gives them the summary and the search (contract). Class: exclude.
- **D-30** (2026-11-11) re F-02, F-03, F-05, F-19 — One screen **Glass models** under Katalog replaces the eurocode register. The list shows the main Eurocode, the other Eurocodes, position, colour and vehicle count, and is searched by any Eurocode, OEM code or vehicle. A Glass model holds its Eurocodes (choosing the main one replaces F-19), OEM codes, position, colour, equipment and fitted vehicles. A Eurocode has no screen of its own. A thumbnail column is ADD-06 (Phase 7). Class: correct.
- **D-31** (2026-11-11) re F-01, BR-01, BR-02, ADD-03 — A vehicle gets structured fields: make (list), model (text), body (list), years from–to, generation (optional text), ARGIC vehicle code (optional, 4 digits). **The V1 name is kept** as the vehicle's own searchable name, with its year text and eurocode block, because the client searches by it; nothing of it is lost in migration. Whether the name stays editable and required for new vehicles → round 2. Class: correct.
- **D-32** (2026-11-11) re F-12, BR-10, G-08 — A dictionary value (position, colour, equipment, producer; also make and body from D-31) has only a name, unique in its list regardless of case. No slug, no status, no ban on `_`. Class: correct.
- **D-33** (2026-11-11) re F-14, Q-12 — Shipping methods stay out of the Catalogue spec until the panel admin is asked (D-08). Class: pending.

Round 2 (2026-11-11) — vehicle name and Glass model workflows. Facts (prod): of 1817 eurocodes, 1586 have live offers, all with a glass type, 86 without colour, 601 without equipment; 231 have no live offer.

- **D-34** (2026-11-11) re F-01, BR-01, D-31 — A vehicle's name is required, unique and editable. For a new vehicle the system proposes `MAKE | MODEL | BODY | years | ARGIC code` from the fields and the admin may change it. Migration copies the V1 name 1:1 and fills the structured fields only where parsing is certain; the name is never rewritten. Only the make is required among the fields. The vehicle search covers the name and the fields. Class: correct.
- **D-35** (2026-11-11) re ADD-01, D-04, D-07 — Creating a Glass model requires its main Eurocode and a position. Colour, equipment, OEM codes, vehicles and further Eurocodes are optional ("unknown" beats a guess: 86 V1 codes have no colour). Migrated models without a position take it from deleted offers, else go to the admin's review list. Class: new.
- **D-36** (2026-11-11) re BR-03, F-19, D-03, D-04 — Eurocodes in a model: an added code is normalised and refused if it already belongs to another model, with a pointer to that model; moving a code to another model is an explicit action and never takes a model's last Eurocode; the main Eurocode can be switched to any of the model's codes; the main one is removed only after another is made main; the last one cannot be removed; a removed Eurocode leaves the catalogue. Class: new. *(User asked to confirm the relation one Eurocode → one Glass model; clarified in round 2b.)*
- **D-37** (2026-11-11) re F-02, D-24 — The Glass model list shows each model's offer count only as a link to the Offer list filtered by that model. Catalogue knows nothing else of Offers. Class: correct.
- **D-38** (2026-11-11) re F-10, D-19 — For Approval, Catalogue gives the admin a candidate search (eurocode, OEM, vehicle) prefilled with the seller's hint, and creation of a new Glass model in place under D-35, with the hint's code proposed as main Eurocode when it looks like one; the new model is selected for the Offer at once. The Approval screen belongs to Offers. Class: new.
- Merging Glass models (round 2 Q4): → D-40.

Round 2b + round 3 part 1 (2026-11-11). Facts: V1 eurocodes ≤ 15 characters, 4 with non-alphanumerics; 1371 OEM codes, a few with a dash, a space or several codes in one field. V2 roles: Super Admin > Admin > User.

- **D-39** (2026-11-11) re D-04, D-36 — Clarifies D-04 without changing it: all Eurocodes of a Glass model are equal (any of them finds the model, none is an alternative); the main one is only the label shown on screens and Adverts, and the admin can switch it any time. One Eurocode belongs to exactly one Glass model (D-03 restated for the user). GLOSSARY updated. Class: correct.
- **D-40** (2026-11-11) re round 2 Q4 — Merging Glass models is not built. Disputed V1 groups are settled with the client before the migration, which runs on corrected data. A duplicate model made later is fixed by hand (re-point its Offers in Offers, remove the duplicate, add its code to the right model). Possible later addition: ADD-07. Class: exclude.
- **D-41** (2026-11-11) re Q-01, IMP-01, D-03 — A Eurocode is stored uppercase, letters and digits only, at most 17 characters. An OEM code is stored as typed, uppercase and trimmed (a dash can be part of a maker's number); lookups and per-model uniqueness compare OEM codes ignoring spaces, dashes and dots. Both are searchable by fragment. Migration splits OEM fields holding several codes. Class: correct.
- **D-42** (2026-11-11) re G-01, G-06, D-18, D-17 — Admin and Super Admin maintain the catalogue (Glass models, Eurocodes, OEM codes, vehicles, dictionaries, catalogue settings); no separate catalogue-editor permission for now. A seller (User) has **no catalogue screen**: they browse and pick a Glass model only in the slide-over of the Offer form, and read the model's data on their Offer's view. Class: correct.
- **D-43** (2026-11-11) re D-26, IMP-06 — Catalogue knows nothing of integration status. A catalogue entry's screen (e.g. a producer) offers an extension point where another module adds its own section, e.g. at the end of the infolist (status, external id, last sync). No first-level tab hook and no global mechanism for now. The section's content belongs to the integration's spec. Class: new.

Round 3 part 2 (2026-11-11) — removal and dictionary clean-up. Facts (prod, live offers per value): positions 31 (24 used; POLE 1–3 LEWE/PRAWE and BOCZNA unused; "Bez kategorii" 1); colours 7 (SOLAR 53, POWŁOKA GRZEWCZA 18 are coatings); producers 62 (carmaker "X OEM" mixed with glass makers; duplicates BYD OEM/OEM BYD, LAMESON/LEMSON; unused LEMSON, OEM BYD, PS); equipment 20, all used.

- **D-44** (2026-11-11) re Q-10, IMP-03, BR-07, D-24 — One rule for Glass models, vehicles and dictionary values: only an unused entry can be deleted; a used one can be **withdrawn** (hidden from picking and search for new assignments, still shown wherever it is already used, restorable). Catalogue checks its own uses; a Glass model or producer referred to by an Offer cannot be deleted (the same usage as D-37; where the check lives is for the architecture, Catalogue still does not know Offers). Renaming is allowed and announced so Adverts refresh. Exception: a vehicle with fitments can be deleted after a warning showing how many Glass models it fits, removing those fitments; vehicles are not withdrawn. Class: correct.
- **D-45** (2026-11-11) re D-25, D-27, Q-14 — Migration takes all position values as they are (accessories and unused POLE/BOCZNA included; unused ones can be deleted later); "Bez kategorii" is not migrated and its model goes to the review list. Combined values such as "PRZEDNIA OPUSZCZANA PRAWA" are not split into position and side. Class: preserve.
- **D-46** (2026-11-11) re D-25 — SOLAR and POWŁOKA GRZEWCZA stay colours, as entered in V1; moving them to equipment is the client's call after migration, done by hand. Logged for the client in `zweryfikwoania_klientem.md` §2. Class: preserve.
- **D-47** (2026-11-11) re D-25, D-13 — Producers migrate as they are ("X OEM" and "Inny" included). Duplicates (BYD OEM/OEM BYD, LAMESON/LEMSON) and unused values (LEMSON, OEM BYD, PS) go to the pre-migration review list with the D-13 disputes; the client confirms what is joined or skipped. Class: preserve.

Round 4 (2026-11-11) — the contract Offers uses (spec §9).

- **D-48** (2026-11-11) re D-24, X-01, BR-20, F-20 — Catalogue gives a **Glass model summary** for one or many ids: main Eurocode and all Eurocodes, OEM codes, position, colour (or none) and equipment as names, fitted vehicles as names, the withdrawn flag, and the thumbnail (ADD-06, if accepted). Class: new.
- **D-49** (2026-11-11) re D-14, D-17, D-38, Q-15, IMP-05 — **Candidate search**: one text matching a fragment of a Eurocode, an OEM code (D-41 comparison) or a vehicle's name or fields; optional filters position, colour, vehicle make; results ordered Eurocode hits, then OEM, then vehicle, each saying what it matched; withdrawn models left out unless asked for. The same search returns matching model ids so Offers can filter its lists by position, colour, Eurocode, OEM and vehicle. Class: new.
- **D-50** (2026-11-11) re D-25, D-44 — Catalogue gives Offers the producer, position and colour lists for forms and filters; withdrawn values are not offered for picking but their names stay readable where already used. Class: new.
- **D-51** (2026-11-11) re D-24, D-26, Q-05 — Catalogue announces: **Glass model changed** (Eurocodes, main Eurocode, OEM codes, parameters or fitment; also raised for every affected model when a vehicle or a position, colour or equipment value changes), **Glass model withdrawn / restored**, **Producer changed / withdrawn**. No "deleted" event (only unused entries are deleted, D-44). Events carry the id; listeners read the summary. Class: new.
- **D-52** (2026-11-11) re D-17, D-42, F-20 — A seller sees the same summary as an admin (Eurocodes, OEM codes, parameters, vehicles, thumbnail) in the Glass model picker and on their own Offer; no offer counts, no withdrawn models. The picker is a dedicated search modal laid out for clarity (custom component; layout at delivery). Class: correct.
- **D-53** (2026-11-11) re D-30 — The admin's Glass model list is laid out as full-width cards rather than a plain table (thumbnail, Eurocodes, OEM codes, parameters side by side), so search results can be compared at a glance, e.g. when checking a disputed code. Supersedes the "table" wording of D-30; layout details at delivery (admin-ui-design). Class: correct.
- Dependency direction confirmed by the user: Offers lives on the catalogue and uses Catalogue's `ModuleApi`; Catalogue does not depend on Offers. The offer-count link (D-37) and the delete guard (D-44) must be solved by the architecture without breaking that.

Phase 7 (2026-11-11) — ADD-06 Glass model thumbnail, grilled.

- **D-54** (2026-11-11) re ADD-06 — The app ships a fixed set of base glass shapes (windscreen, rear, side drop, side fixed/body, roof, accessory, unknown). Catalogue settings, section **Position → shape**, give each position value a shape and a "mirrored" switch for left/right. An unmapped position shows "unknown". Class: new.
- **D-55** (2026-11-11) re ADD-06 — Section **Colour → fill** gives each colour any fill colour (picker) and opacity. No colour or an unmapped one shows neutral clear glass. No uploading of custom SVGs. Class: new.
- **D-56** (2026-11-11) re ADD-06 — Section **Equipment → pictogram** gives each equipment value a pictogram from a fixed set shipped with the app; an unmapped value shows a universal pictogram. A Glass model's equipment is shown as a row of pictograms with the thumbnail, each with a tooltip of the value's full name. Class: new.
- **D-57** (2026-11-11) re ADD-06, D-48, D-52, D-53 — The thumbnail and pictograms appear wherever they help: the admin's Glass model cards and the seller's picker modal; they are part of the summary (D-48), so Offers may use them in its lists. Not on the Offer view (it shows photos of the real piece) and not in Adverts. Only admins edit the mappings (D-42); a mapping change is not a Glass model change and raises no event. Class: new.
- **D-58** (2026-11-11) re ADD-04, ADD-05 — Phase 7 close: ADD-05 (closed ARGIC-lettered equipment list) and ADD-04 (VIN lookup) are not in the first spec; no new ideas from the user. ADD-02, ADD-07 stay excluded (D-12, D-40), ADD-03 is covered by D-31/D-34, ADD-A01 waits for the client. The user confirmed a shared understanding of the existing functionality (Phase 6 done). Class: exclude.

Phase 9 (2026-11-11) — data migration. Facts in `.scratch/data-migration/catalogue/spec.md` §4 and `review-list.md` (prod, aggregate).

- **D-59** (2026-11-11) re D-13, G-02 — Grouping counts links from **live and deleted** V1 offers (user: the richest catalogue for future glass): 1817 codes → 1673 Glass models, 91 multi-code; the 3 joins that exist only through deleted offers are applied and listed for the client. Class: correct.
- **D-60** (2026-11-11) re D-09, D-35, BR-05 — Main Eurocode = most often at priority 0 in the group's offers (ties: more offers, then alphabetical). Position, colour, equipment = value shared by live offers, else from deleted offers; conflicts → review list, default most frequent, ties newest offer. Models with no offer → review list with the ARGIC-letter suggestion as default; no suggestion and no answer → not migrated, reported. Class: new.
- **D-61** (2026-11-11) re Q-01, D-41 — Odd codes: leading spaces and dots removed by normalisation; `TEST123456` dropped (not real); other non-standard codes (70, several OEM-shaped) go to the review list, default kept as Eurocode. Class: correct.
- **D-62** (2026-11-11) re D-14, Q-16, F-08, F-09 — OEM: `eurocodes.oem_codes` → model OEM codes (multi-code entries split); `eurocodes.oem_code` dropped (all already in the list); `offers.oem` joins the offer's model; OEM of offers without a link go to the Offers import for the hint. Class: correct.
- **D-63** (2026-11-11) re D-15, BR-07 — `car_offer`: the 3 pairs outside eurocode fitment join the offer's model; vehicle names of the 59 offers without a link go to the Offers import for the hint. Class: correct.
- **D-64** (2026-11-11) re D-31, D-34 — Vehicles: name 1:1; ARGIC code from its last 4 digits; make from `brand`; model as is; body from trimmed `type` with joins only as the client confirms (review F); years and generation parsed when certain, otherwise the raw `year` text goes to generation. Class: correct.
- **D-65** (2026-11-11) re X-06, D-33 — Creation and last-change dates are kept; change history starts at migration. Dropped: slugs, statuses, `tmp_session`, `offer_extras`, link keys and priority after choosing main, legacy `offers.eurocode`/`alt_eurocodes` (links are authoritative; all 97 alternatives are in the register). Shipping not migrated now; it stays in the V1 dump (D-33). Fixes that need no reusable process are made by the client directly in V1 before the import (`zweryfikwoania_klientem.md` §3). Class: correct.
- **D-66** (2026-11-11) re D-28 — Process change (user): the Offers module grilling is postponed; Catalogue issues are cut and delivered first from this spec. If Offers later needs more from Catalogue's module interface, it is added then and recorded as a `D-` in both documents. Class: n/a (process).

