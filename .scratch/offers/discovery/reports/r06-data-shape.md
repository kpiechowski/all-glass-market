# r06 — verify: data shape of the catalogue and offers on the production dump

Question: Do offers that share a eurocode in practice share the same glass parameters (type, colour, equipment), while producer, year, usage state and price vary per offer? · V1 commit: 511ce3b

Data: DB `allglass_v1_prod` (read-only SELECT aggregates). Dump date range: offers created 2023-04-13 .. 2026-10-01; sales up to 2026-10-08. All findings below are `observed` (measured on the dump) unless tagged otherwise. Nothing personal is quoted.

## Summary
- Yes, the hypothesis holds. Among 238 eurocodes with >=2 non-deleted offers (574 offers): same type 99.6%, same colour 97.5%, same equipment set 93.3%, all three together 91.2%. Producer is the same in only 46.2%, year in 41.6% (67.6% if offers without a year are ignored), usage state in 66.8%.
- Type/colour/equipment are therefore **near-invariants of the eurocode**, not free per-offer fields; the exceptions are few (1 type conflict, 6 colour conflicts, 16 equipment conflicts) and look like data-entry variance or genuine variants (see examples).
- Producer, usage state, year, price vary per offer. Year is filled on only 590 of 2046 offers (almost only on Używana/Outlet; 1 of 697 Nowa), so year is a used-piece attribute.
- Catalogue is small and clean: 1817 eurocodes, no duplicates after normalisation, all 4 dictionaries have only status Aktywna (no pending rows in the dump).
- car_offer (offers -> cars directly) is redundant with eurocode->car in 722 of 790 offers (identical sets).
- Offers are highly concentrated: 3 sellers own 1936 of 2046 offers (94.6%); 11 sellers total.
- Quantity is almost always 1; offers have photos (only 1 non-deleted offer has none).

## Findings

### A. Catalogue

**A1. Eurocodes and OEM codes**
- Tag: observed · Impact: normal
- Query: `SELECT COUNT(*), SUM(oem_code<>'') FROM eurocodes`; `SELECT IFNULL(JSON_LENGTH(oem_codes),-1),COUNT(*) FROM eurocodes GROUP BY 1`.
- Result: 1817 eurocodes. Legacy `oem_code` filled on 1244. `oem_codes` JSON: NULL 425, empty array 24, 1 code 1365, 2 codes 3, never more. So 1368 have at least one OEM code in the JSON; effectively 1 OEM code per eurocode (2 in 3 cases).
- Note: legacy `oem_code` count (1244) differs from JSON-nonempty count (1368), so the two columns are not in sync (which one is filled only in some rows was not cross-tabulated; `unknown` how they overlap).

**A2. Eurocode code length and normalisation**
- Tag: observed · Impact: normal
- Query: `SELECT CHAR_LENGTH(code),COUNT(*) FROM eurocodes GROUP BY 1`; duplicates: group by `UPPER(REPLACE(REPLACE(TRIM(code),' ',''),'-',''))` having count>1.
- Result: length 5:1, 6:3, 7:48, 8:139, 9:181, 10:151, 11:486, 12:348, 13:214, 14:175, 15:71 (mode 11-12; typical form is 4 digits + letters, e.g. 3367AGACHMVWZ). Duplicate groups after normalisation: **0**. 4 codes are not in canonical form: 2 with a leading space (` 6564AGACMVZ1B`, ` 8545RGNH3FD`), 1 with a dash (`1-05019706AA`, looks like an OEM number used as eurocode), 1 test value (`test123456`).

**A3. Cars and links**
- Tag: observed · Impact: normal
- Result: 1188 cars; 1185 have >=1 eurocode (3 without). Links per eurocode (car_eurocode): 0 cars:34 eurocodes, 1:1015, 2:552, 3:167, 4:43, 5:3, 6:2, 8:1. (So 1783 of 1817 eurocodes have a car; 1 car per eurocode is the norm, up to 8.) 45 eurocodes have no offer at all (catalogue entries without a listing).

**A4. car_offer vs cars reachable via eurocode**
- Tag: observed · Impact: normal
- Query: per offer in car_offer, compared the set of direct cars with the set reachable through eurocode_offer -> car_eurocode.
- Result: car_offer 1531 rows, all distinct pairs, covering **790 offers** and 734 cars (68 of those offers are soft-deleted). Per offer: identical sets 722; direct set is a subset of the via-eurocode set 6; via set is a subset of direct 2; partial overlap 1; offer has direct cars but no car via any eurocode 21; fully disjoint 0. Offers that can reach a car via eurocode at all: 2006 of 2046. Conclusion: for the offers that have it, car_offer is a copy of the eurocode's cars in 91% of cases; it is not an independent source of truth.

**A5. Dictionaries**
- Tag: observed · Impact: normal
- Query: count by `status` and `NOT EXISTS` usage per dictionary.
- Result (all rows `Aktywna`, zero `Oczekuje zatwierdzenia`):

| Dictionary | Rows | Unused by any offer | Most used |
|---|---|---|---|
| offer_colors | 7 | 0 | ZIELONA 1740, SOLAR 58, BEZBARWNA 56, CIEMNA 39, CIEMNOZIELONA 37, POWŁOKA GRZEWCZA 18, NIEBIESKA 17 |
| offer_types | 31 | 7 | PRZEDNIA 1244, TYLNA 186, PRZEDNIA OPUSZCZANA PRAWA 125, PRZEDNIA OPUSZCZANA LEWA 94, TYLNA KAROSERYJNA LEWA 59, TYLNA KAROSERYJNA PRAWA 55 |
| offer_producers | 62 | 2 | Fuyao 302, Saint Gobain Sekurit 176, Xinyi 176, AGC Automotive 148, VW OEM 120, Pilkington 118, Audi OEM 83, Mercedes OEM 65, Ford OEM 53, NordGlass 53 |
| offer_equipment | 20 | 0 | POLE VIN 1071, CZUJNIK DESZCZU/ŚWIATŁA 929, AKUSTYCZNA 680, WSPORNIK KAMERY 636, OGRZEWANIE 324, PAS PRZYCIEMNIAJĄCY 168, HEAD-UP 109, ANTENA 106 |

- Offers with missing values (all 2046 offers): colour NULL 81, type NULL 9, producer NULL 74, no equipment at all 695 (34%), year empty 1456. So colour/type/producer are not strictly required in data, and "no equipment" is a normal state.
- Producer dictionary mixes real manufacturers and car-brand "OEM" entries (VW OEM, Audi OEM, ...): the same dictionary expresses "who made it" and "original vs aftermarket".

### B. Offers per eurocode
**B1. Offers per primary eurocode** (primary = lowest priority per offer; priorities in data are 0 (2009 links), 10 (116), 20 (45), 30 (16), 40 (8), 50 (1); no ties within an offer)
- Tag: observed · Impact: normal
- Offers per primary eurocode (incl. deleted offers): 1 offer: 1384 eurocodes; 2: 185; 3: 41; 4: 19; 5: 6; 6: 2; 7: 2. Eurocodes with >=2 primary offers: 255.
- Per any linked eurocode (distinct offers): 1: 1481; 2: 208; 3: 51; 4: 21; 5: 7; 6: 2; 7: 2. Eurocodes with >=2 offers (any link, incl. deleted): 291; excluding deleted offers: 238 (574 offers); with >=3 non-deleted: 65 (228 offers). 45 eurocodes have no offer (1772 + 45 = 1817).
- So the typical eurocode has exactly one offer; a minority (about 16% of eurocodes with offers) has several offers, up to 7.

**B2. Offers with no eurocode link: 37**
- Tag: observed · Impact: normal
- Split (without_eurocode, status, deleted): 25 Usunięta (all soft-deleted, flag 0); 7 Aktywna (flag 0); 3 Odrzucona (flag 0); 1 Archiwalna (flag 0); 1 Aktywna with without_eurocode=1 (the single flagged offer). So 8 active offers without a link, 7 of them without the flag set (data does not enforce "no link implies flag"). The `offers.eurocode` text column is filled on 1898 offers, `alt_eurocodes` JSON on 55 (legacy denormalised columns; relationship to eurocode_offer not compared).

**B3. Offers with more than one eurocode**
- Tag: observed · Impact: normal
- Links per offer: 0:37, 1:1893, 2:71, 3:29, 4:8, 5:7, 6:1. So 116 offers (5.7%) carry alternative eurocodes (priority 10, 20, ...).

### C. Consistency within a eurocode group (key question)
Method: group = eurocode, counted over eurocode_offer links (any priority), offers not soft-deleted, group size >= 2. "Same" = exactly one distinct value in the group; NULL counts as a value (checked: no group is made inconsistent or consistent only by NULL colour/type, see below). Equipment compared as the sorted set of offer_offer_equipment ids (empty set = one value). Query template: CTE `ol` (eurocode_offer JOIN offers + GROUP_CONCAT of equipment ids), CTE `g` (COUNT(DISTINCT ...)=1 per attribute, HAVING n>=k), then AVG per attribute.

**C1. Result table (share of groups where all offers agree)**
- Tag: observed · Impact: high (decides whether type/colour/equipment belong to the product or to the offer)

| Scope | Groups | Offers | Type | Colour | Equipment | Type+colour+equip | Producer | Year (NULL as value) | Year (ignoring NULL) | Usage state |
|---|---|---|---|---|---|---|---|---|---|---|
| any link, non-deleted, >=2 (main) | 238 | 574 | 99.6% | 97.5% | 93.3% | 91.2% | 46.2% | 41.6% | 67.6% | 66.8% |
| any link, non-deleted, >=3 | 65 | 228 | 100.0% | 95.4% | 93.8% | 89.2% | 35.4% | 26.2% | 50.8% | 50.8% |
| any link, incl. deleted, >=2 | 291 | 714 | 99.3% | 97.9% | 91.8% | 89.7% | 42.3% | 44.7% | 73.2% | 59.5% |
| primary eurocode only, non-deleted, >=2 | 204 | 493 | 99.5% | 98.0% | 94.6% | 92.2% | 45.1% | 43.1% | 67.6% | 66.7% |
| primary only, non-deleted, >=3 | 54 | 193 | 100.0% | 94.4% | 92.6% | 87.0% | 29.6% | 29.6% | 53.7% | 46.3% |
| any link, status Aktywna only, >=2 | 159 | 374 | 99.4% | 98.1% | 93.7% | 91.2% | 51.6% | 52.8% | 74.2% | 67.9% |

- Type, colour, equipment and producer all the same: 41.6% in the main scope, 32.3% for groups >=3.
- NULL check: in all 238 main groups every offer has colour, type and producer set (0 groups with a NULL), so the colour and type figures are not inflated by NULLs. Producer consistency among those 238 groups: 46.2% (same).
- Interpretation: type is effectively fixed per eurocode (it is the glass position, which the eurocode encodes). Colour is fixed in 97.5%. Equipment is the same in 93.3% and in 6 of the 16 conflicts the only difference is "no equipment recorded" vs "has equipment" (looks like incomplete entry). Producer, year and usage state genuinely vary: with 3+ offers on a eurocode only 35% share a producer and 26% share a year.
- Price: not compared within groups (by design, price is per offer); overall price per non-deleted offer min 30, p25 120, median 200, p75 400, max 4300 (see D).

**C2. Examples of inconsistent groups (eurocode + differing dictionary names only)**
- Tag: observed · Impact: normal
- Type (the only one in 238): `3367AGACHMVWZ`: PRZEDNIA | TYLNA.
- Colour (all 6 colour conflicts): `8658ACKCKMVZ`: POWŁOKA GRZEWCZA | ZIELONA; `7623ACKCKMVZ1L`: SOLAR | ZIELONA; `7824ACKCKMVZ`: POWŁOKA GRZEWCZA | SOLAR; `7820ACKCKMVZ1L`: POWŁOKA GRZEWCZA | ZIELONA; `8626ACKCKMVZ1L`: SOLAR | ZIELONA; `8627ACKCKMVZ1L`: SOLAR | ZIELONA. All are heated/solar-coated glass variants; the confusion is among coating colours, which is typical sub-variant noise.
- Equipment (examples): `7296AGAV1C` and `3775AGAV1C`: none | AKUSTYCZNA; `8374AGAW`: AKUSTYCZNA | none; `4447AGABLHIMOVW`: {AKUSTYCZNA, CZUJNIK PAROWANIA, OGRZEWANIE WYCIERACZEK, PAS PRZYCIEMNIAJĄCY, POLE VIN} | {AKUSTYCZNA, CZUJNIK DESZCZU/ŚWIATŁA, CZUJNIK PAROWANIA, OGRZEWANIE, PAS PRZYCIEMNIAJĄCY, POLE VIN}; `8618AGAMVZ1W`: three different subsets of {AKUSTYCZNA, CZUJNIK DESZCZU/ŚWIATŁA, POLE VIN}; `7623ACKCKMVZ1L`: differs by CZUJNIK DESZCZU/ŚWIATŁA. 6 conflicts are only "none vs some".

### D. Offers
**D1. Offers per seller** (incl. deleted)
- Tag: observed · Impact: normal
- 11 sellers; bucketed offers: >500 offers: 2 sellers (1649 offers); 101-500: 1 seller (287); 21-100: 2 (79); 6-20: 2 (24); 2-5: 2 (5); 1: 2 (2). Shares of the 11 sellers: 45.2%, 35.4%, 14.0%, 2.6%, 1.2%, 0.7%, 0.5%, 0.3%... Top 3 sellers hold 94.6%.

**D2. Usage state, rating, quantity**
- Tag: observed · Impact: normal
- usage_status (all / non-deleted): Używana 1121/1028, Nowa 697/554, Outlet 228/176.
- Rating by usage: Nowa: 5 = 696, 4 = 1 (so effectively always 5). Outlet: 5 = 200, 4 = 20, 3 = 6, 2 = 1, 1 = 1. Używana: 5 = 571, 4 = 236, 3 = 164, 2 = 87, 1 = 63. Rating is a condition grade that is meaningful for Używana/Outlet only; no NULL ratings.
- Quantity <>1 (16 offers): Nowa: qty 2 on 8, qty 3 on 2, qty 13 on 1; Używana: qty 4 on 3, qty 2 on 2; Outlet: none. All other offers (2030) have quantity 1, i.e. an offer is in practice one physical piece, and multi-quantity appears almost only for new glass (11 of 16).
- Year filled: Używana 565/1121, Outlet 24/228, Nowa 1/697.

**D3. Price** (non-deleted offers, 1758; none NULL or 0)
- Overall min 30, p25 120, median 200, p75 400, max 4300, avg 339. By usage: Używana min 30 / avg 222 / max 1300; Nowa 30 / 445 / 4300; Outlet 100 / 687 / 3000.
- By type (n / min / avg / max): PRZEDNIA 1068 / 70 / 463 / 3500 (median 300); TYLNA 155 / 100 / 273 / 2100; PRZEDNIA OPUSZCZANA PRAWA 111 / 30 / 79 / 400; PRZEDNIA OPUSZCZANA LEWA 79 / 30 / 71 / 300; TYLNA OPUSZCZANA PRAWA 50 / 30 / 93 / 300; TYLNA KAROSERYJNA PRAWA 49 / 40 / 141 / 800; TYLNA KAROSERYJNA LEWA 46 / 40 / 135 / 1300; USZCZELKA SZYBY PRZEDNIEJ 46 / 30 / 82 / 150; TYLNA OPUSZCZANA LEWA 34 / 30 / 66 / 130; STAŁA DRZWI TYLNE PRAWE 26 / 30 / 100 / 400.

**D4. Time from creation to sale**
- Tag: observed (with a caveat) · Impact: normal
- offer_sales: 318 rows, 317 offers (one offer has 2 sale rows), sum of quantity 314, sold_at range 2025-02-14 .. 2026-10-08. 314 of 317 Sprzedana offers have a sales row; 3 have none (and no sold_date). 279 of 317 have sold_date filled.
- Days from offers.created_at to first sale (317 offers): same day 0; 1-7: 1; 8-30: 13; 31-90: 43; 91-180: 54; 181-365: 66; >365: 140. Caveat: 140 offers show >1 year; offers.created_at dates back to 2023 and the monthly creation curve (D5) suggests bulk migration/import in Aug-Jan 2025/26, so created_at may be a legacy date rather than the V1 publication date. Whether it is reliable is `unknown` (no way to tell from data alone).

**D5. Offers created per month** (last 18 months; months not listed had 0)
- Tag: observed · Impact: normal
- 2025-05: 5; 06: 7; 07: 10; 08: 50; 09: 279; 10: 150; 11: 195; 12: 214; 2026-01: 247; 02: 79; 03: 61; 04: 116; 05: 24; 06: 29; 07: 30; 08: 0; 09: 10; 10: 6. Peak Sep 2025 to Jan 2026 (1085 offers), then roughly 25-30 a month in mid-2026 (spike 116 in April 2026).

**D6. Photos**
- Tag: observed · Impact: normal
- media for model App\Models\Offer: collection `offer-gallery` 8390 files on 2039 offers; collection `offer-private-gallery` 297 files on 276 offers (271 non-deleted offers have a private photo). The gallery collection is named `offer-gallery` (not "gallery").
- Photos per non-deleted offer (offer-gallery): 0: 1; 1-2: 415; 3-5: 942; 6-10: 398; >10: 2. Average 4.1, max 14. So 99.9% of live offers have photos.

**D7. Duplicates as identity ("duplikuj" signal)**
- Tag: observed (signal, not proof) · Impact: normal
- Groups of >=2 non-deleted offers with the same seller, same primary eurocode, same usage_status and same creation day: **15 groups, 31 offers**, max 3 per group, from 4 sellers. By usage: Używana 12, Outlet 2, Nowa 1. Within these 15 groups: price differs in 5, year in 6, producer in 5 (so most are true copies of near-identical pieces; a minority are different physical pieces).
- Looser version (same seller + primary eurocode + usage_status, any day): 125 groups, 285 offers. Same-eurocode, same-state multiples are therefore common over time, mainly for used glass (several used pieces of one eurocode) but also across days.
- Conclusion: several physical pieces of the same eurocode and state are normal and are modelled as separate offers (quantity stays 1), confirming the "offer = one physical piece" reading.

## Contradictions and defects
- `defect?` 7 non-deleted Aktywna offers have no eurocode link while without_eurocode = 0 (and 3 Odrzucona, 1 Archiwalna); only 1 offer has the flag set. Data does not enforce flag consistency. Evidence: query B2.
- `defect?` Eurocode codes with a leading space (2), a dash (1) and a test value `test123456` exist in the production catalogue (A2).
- `defect?` Legacy `eurocodes.oem_code` filled 1244 vs `oem_codes` JSON non-empty 1368: the two OEM columns disagree in coverage (A1).
- 3 offers in status Sprzedana have no offer_sales row; 1 offer has two sale rows (D4).
- Sub-variant noise: colour conflicts within a eurocode are always among coating colours (C2).

## Leads outside scope
- Photo storage: `offer-private-gallery` (297 files, 276 offers) is a second, private image collection; its purpose and visibility rules are not covered here.
- Seller concentration (3 sellers = 94.6% of offers) affects how much multi-seller features matter in V2.
- offers.created_at likely reflects import timing (bulk in Sep 2025 - Jan 2026); relevant for any V2 "age of listing" logic.
- Legacy denormalised columns on offers (`eurocode`, `alt_eurocodes`, `oem`, `vim`, `gallery`, `image`, `attachments`) co-exist with relations; not compared with the relations here.

## Could not establish
- How `eurocodes.oem_code` and `oem_codes` overlap row by row (not cross-tabulated); a one-line follow-up query would settle it.
- Whether offers.created_at is the real publication date (needs a business answer or the import history).
- Whether the 16 equipment conflicts and 6 colour conflicts are data-entry errors or genuine variants of the same eurocode (needs a business answer; the data shows only "none vs some" in 6 equipment cases).

## Coverage ledger
- Searched: information_schema.columns for offers, eurocodes, cars, car_eurocode, car_offer, eurocode_offer, offer_colors, offer_types, offer_producers, offer_equipment, offer_offer_equipment, offer_sales; then aggregate SELECTs (counts, GROUP BY, COUNT DISTINCT, window percentiles) on those tables and `media`.
- Examined: tables above, read-only. V1 code and migrations were not read (column names taken from information_schema).
- Matched, not examined: WooCommerce/Otomoto tables (out of scope); free-text columns (user_description, description, notes) deliberately untouched.
