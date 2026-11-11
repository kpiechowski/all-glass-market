# Catalogue — V1 data migration spec

<!-- Written in Phase 9. V1 side: tables and columns with evidence. V2 side: the information named in
     the functional spec's terms, never V2 column names; whoever builds the import maps those. -->

- **Functional spec:** `.scratch/catalogue/functional-spec.md` §8 · **Discovery:** `.scratch/catalogue/discovery/discovery.md` (decisions D-59–D-65 for this spec)
- **Ticket:** `ticket.md` beside this file · **Review list:** `review-list.md` beside this file
- **Evidence:** V1 migrations (r01, r02), production dump `allglass_v1_prod` (2026-11-09, aggregate reads, 2026-11-11), Offers r06 data shape.

## 1. Sources

| V1 table | Rows (prod) | Defined by | Holds |
|---|---|---|---|
| `eurocodes` | 1817 | `2025_08_30_131935_create_eurocodes_table`, `2026_03_28_000001_add_oem_codes_to_eurocodes_table` | code, OEM codes |
| `eurocode_offer` | 2195 | same | offer ↔ eurocode links with priority (source of grouping, main Eurocode and parameters) |
| `car_eurocode` | 2827 | same | fitment per eurocode |
| `cars` | 1188 | `2024_10_06_144444_create_cars_table` | vehicles |
| `car_offer` | 1531 | `2025_01_05_145235_car_offer_table` | legacy offer ↔ car links |
| `offer_types` | 31 | `create_offer_types_table`, `add_shipping_to_glass_type` | positions |
| `offer_colors` | 7 | `create_offer_colors_table` | colours |
| `offer_equipment` | 20 | `create_offer_equipment_table` | equipment |
| `offer_offer_equipment` | 4259 | `create_offer_offer_equipment` | offer ↔ equipment (source of model equipment) |
| `offer_producers` | 62 | `create_offer_producers_table` | producers |
| `offer_extras` | 0 | `create_offer_extras_table` | nothing (dead, Q-11) |
| `shipping_methods` | 7 | — | shipping (D-33, not migrated) |
| `offers` (catalogue columns only) | 2046 | `2024_10_06_151733_create_offers_table` | `eurocode`, `alt_eurocodes`, `oem`, `vim`, `without_eurocode`, `offer_type_id`, `offer_color_id`, `offer_producer_id`, `shipping_method_id` |

`car_brands` exists in V1 migrations but not in production (r01: dead).

## 2. Mapping

### Glass models (built from `eurocodes` + `eurocode_offer` + offers)

| V1 table.column | Meaning in V1 | Fate | V2 information | Rule (decision) |
|---|---|---|---|---|
| `eurocodes.id` | eurocode key | transform | Glass model identity (grouping key only) | Eurocodes linked to the same V1 offer, **live or deleted**, join one Glass model, transitively (D-13, D-59). 1817 codes → 1673 models, 91 with 2–6 codes |
| `eurocodes.code` | eurocode | transform | Eurocode | Normalised: uppercase, letters and digits only (RULE-03); 0 collisions after normalisation. `TEST123456` dropped; other odd codes per review list D (D-61) |
| `eurocode_offer.priority` | 0 = primary on that offer | transform | Main eurocode | Main = the code most often at priority 0 in the group's offers; ties → the code with more offers → alphabetical (D-60) |
| `eurocode_offer.offer_id` | link to offer | transform | (output for Offers) offer → Glass model map | Each V1 offer gets the Glass model of its eurocodes; handed to the Offers import |
| `eurocode_offer.created_at/updated_at` | link timestamps | drop | — | — |
| `offers.offer_type_id` via links | glass type | transform | Position of the model | Value shared by the group's live offers; none live → deleted offers; conflict → review B, default most frequent, ties newest offer (D-09, D-35, D-60). No offer at all → review C, default ARGIC-letter suggestion, else not migrated (D-60) |
| `offers.offer_color_id` via links | colour | transform | Colour of the model | Same rule; may stay empty (D-35) |
| `offer_offer_equipment` via links | equipment of the offer | transform | Equipment of the model | Same rule on the equipment set (D-60) |
| `eurocodes.oem_codes` | OEM list (JSON) | transform | OEM codes of the model | Union over the group's eurocodes; uppercase, trimmed; duplicates by the RULE-04 comparison removed; the 2 entries holding several codes split on `,` (D-41, D-62) |
| `eurocodes.oem_code` | legacy single OEM | drop | — | Every value is already in `oem_codes` (checked) (D-62) |
| `offers.oem` | OEM imported from Woo | transform | OEM codes of the offer's model | 637 offers (582 live); 67 add codes their model lacks; split on `,;/`. 17 offers without a link → handed to the Offers import for the identification hint (D-14, D-62) |
| `eurocodes.created_at` | creation date | keep | Glass model created | Earliest of the group (D-65) |
| `eurocodes.updated_at` | last change | keep | Glass model last changed | Latest of the group (D-65) |

### Fitment

| V1 table.column | Meaning in V1 | Fate | V2 information | Rule (decision) |
|---|---|---|---|---|
| `car_eurocode.car_id`, `.eurocode_id` | fitment per eurocode | transform | Fitment of the Glass model | Union of the group's eurocodes' fitment (D-11) |
| `car_offer.offer_id`, `.car_id` | legacy offer ↔ car | transform | Fitment of the offer's model | 3 pairs outside the eurocode fitment are added to the offer's model; offers without any link (59) hand their vehicle names to the Offers import for the hint (D-15, D-63) |
| `car_offer.id`, timestamps; `car_eurocode` timestamps | keys, dates | drop | — | — |

### Vehicles

| V1 table.column | Meaning in V1 | Fate | V2 information | Rule (decision) |
|---|---|---|---|---|
| `cars.name` | unique display name, client's search format | keep | Vehicle name | Copied 1:1, never rewritten (D-34) |
| `cars.name` (last 4 digits) | ARGIC vehicle block | transform | ARGIC vehicle code | Taken when the name ends with 4 digits (1165 of 1188) (D-64) |
| `cars.brand` | make | transform | Make (dictionary value) | Trimmed; 54 values become the make list (D-31, D-64) |
| `cars.model` | model | keep | Model | As is (D-64) |
| `cars.type` | body / version | transform | Body (dictionary value) | Trimmed; 68 values become the body list; joins only as the client confirms (review F) (D-64) |
| `cars.year` | years and generation mixed | transform | Years from/to, generation | Parsed when certain (≈ 99%: `2011-2014 VII`, `C 2003-2008`, `2014-`); otherwise the raw text goes to generation, so nothing is lost (D-64) |
| `cars.slug` | WP slug | drop | — | D-32, D-34 |
| `cars.created_at/updated_at` | dates | keep | Vehicle created / last changed | D-65 |

### Dictionaries

| V1 table.column | Meaning in V1 | Fate | V2 information | Rule (decision) |
|---|---|---|---|---|
| `offer_types.name` | glass type | keep | Position value | All 30 values except "Bez kategorii" (D-45) |
| `offer_colors.name` | colour | keep | Colour value | All 7, coatings included (D-46) |
| `offer_equipment.name` | equipment | keep | Equipment value | All 20 |
| `offer_producers.name` | producer | keep | Producer value | All 62, unless the client joins or drops some in V1 first (review E) (D-47) |
| `*.slug`, `*.status`, `*.tmp_session` | WP key, approval, session proposals | drop | — | D-25, D-32 (all prod values are Aktywna) |
| `*.created_at/updated_at` | dates | keep | created / last changed | D-65 |
| `offer_types.shipping_method_id` | default shipping | drop (for now) | — | D-33: stays in the V1 dump until shipping is decided |
| `offer_extras.*` | dead | drop | — | 0 rows (D-25) |
| `shipping_methods.*` | shipping | drop (for now) | — | D-33 |

### Offer columns read by this import

| V1 table.column | Meaning in V1 | Fate | V2 information | Rule (decision) |
|---|---|---|---|---|
| `offers.eurocode` | legacy text / seller's typed code | drop | — | Links are authoritative (r04). Text of the 9 live offers without a link → Offers import (hint) (D-65) |
| `offers.alt_eurocodes` | legacy alternatives | drop | — | All 97 codes already in `eurocodes` (checked) (D-65) |
| `offers.vim` | VIN | drop | — | D-16 |
| `offers.without_eurocode` | toggle | drop here | — | Offers import (D-10) |
| `offers.offer_producer_id` | producer | — | — | Mapped by the Offers import onto the migrated producer list |
| `offers.shipping_method_id` | shipping override | drop (for now) | — | D-33 |

## 3. New information

- **Withdrawn flag** on Glass models and dictionary values: all start active.
- **Thumbnail mappings** (position → shape, colour → fill, equipment → pictogram): start empty; the admin fills them after migration (unmapped → "unknown" shape, clear glass, universal pictogram).
- **Vehicle fields** with no V1 source (years, generation where unparsed): empty.

## 4. Data quality

- Grouping: 91 multi-code models; **27 groups** whose codes differ beyond the vehicle prefix; **3 joins** exist only through deleted offers (review A, H).
- Parameter conflicts on live offers: **17 groups** (9 equipment, 5 colour incl. coatings, 1 position, some mixed) (review B).
- **186** models have only deleted offers (parameters from them); **45** have none (review C; many look like OEM numbers).
- **70** codes do not match "4 digits + 2 letters"; many are valid (new ARGIC letter prefixes, maker codes), some look like OEM numbers (review D).
- Odd codes: two with leading spaces (fixed by normalisation), `5394GZPR5P..` (dots removed), `1-05019706AA` (review D), `TEST123456` (dropped).
- OEM: 1371 entries, 2 holding several codes; 33 OEM codes on more than one eurocode (allowed, D-14).
- Vehicles: 1188; 3 without fitment (migrated anyway); 7 field combinations repeat and differ only by the ARGIC block; name contains the year text in 1154 cases.
- Producers: duplicates and unused values (review E).
- Character set: V1 names hold Polish characters; read the dump as UTF-8.

## 5. Run plan

- **Order:** first among the module imports — Catalogue before Offers, because the Offers import needs the offer → Glass model map, the producer list, and the hint material handed over here.
- **Before the run:** regenerate `review-list.md` on current V1 data; the client fixes what they can in V1; the answers V1 cannot hold (review C positions, review D "move to OEM of model X") go into the import's mapping file.
- **Idempotency key:** the normalised Eurocode for Glass models (a model is found again by any of its codes), the vehicle name for vehicles, the name for dictionary values. A re-run updates in place and creates nothing twice.
- **Verify a run:** counts of Glass models, Eurocodes, OEM codes, fitments, vehicles and values per dictionary against the source counts above (minus documented drops); every V1 eurocode either mapped to a model or listed as dropped; the run report lists every default applied.
- **After the run:** the admin fills the thumbnail mappings and checks the run report.

## 6. Open questions

- None blocking. The client's answers to the review list arrive before the run (`zweryfikwoania_klientem.md` §3).
