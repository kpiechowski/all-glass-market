# R05 — Research: Eurocode and the automotive-glass catalogue

Web research, done without reading any project code. Every claim is cited inline as [n], and the list at the end gives the URL for each number. **Source quality:** ARGIC's own technical documentation (the code "Matrix" and its code tables) is open only to members [1][3]. As a result, the position-by-position decoding below comes from distributor and installer pages (Polish, Russian and UK trade sites), plus real codes from Pilkington Poland's price list. Treat those tables as **trade-documented, not ARGIC-authoritative**. Anything I could not source is marked **(unverified)**.

---

## 1. What a Eurocode is and how it is built

### Issuer and governance
- Saint-Gobain, Pilkington and Belron founded **A.R.G.I.C. (Automotive Replacement Glass Identification Center)** in **1993**. It is a non-profit association, and it appointed an independent company as the **codification center** [1].
- ARGIC has three stated aims: develop a standard code structure (the **Eurocode**), build and maintain a database of parts for vehicles on the European market, and keep its members informed [1].
- **Executive members** supply data to the codification center. **Non-executive members** comment on it. Members query the database through an ARGIC intranet [1][3]. AGC is a long-standing member [2].
- The database holds **more than 93,560 references**, covering glass and the accessories used to fit it [1]. AGC gives "nearly 100,000" [2]. The goal is for codes to exist **before a vehicle launches in Europe** [1].
- The code structure is under copyright, and "ARGIC Eurocode" is a registered trademark [1][4].
- ARGIC describes the code as "self-explanatory", because the codification logic stays the same and is supported by the "matrix" [1].

### Length and the 2025 change
- The maximum length was **15 characters** [1].
- Codes created **after 1 January 2025** may have up to **17 characters**, and the **modification slot grows from 2 to 3 characters**. Codes created before 31 December 2024 stay unchanged, so **both formats coexist** [2][3]. The names of features and values ("denomination") are also being reviewed [2].

### Structure, read left to right from vehicle to glass detail [3]
The classic (pre-2025) windscreen layout below is assembled from [5][6][7][8]:

| Positions | Field | Values seen in sources |
|---|---|---|
| 1–4 | **Vehicle (model) number**. Sources usually say 2 digits for the make and 2 for the model [5][6][7] | 53 = Mercedes, 28 = W124 [7]; 35 = Ford, 66 = Focus II [6]; 54/28 = Mercedes Vito W638 [8]; 8631 = Audi A4 (2015) [9] |
| 5 | **Glass position/type** | A windscreen, B rear (backlight), L left side, R right side, F flat side glass [6][7][8]. Some sources add C "alternative windscreen" and D "windscreen/accessories" [6]. Roof letters exist but were not found in sources **(unverified)** [3] |
| 6–7 | **Colour/tint + technology** | CL clear, GN green, GS green solar/athermal, BL blue, BS blue solar, BZ bronze, GY grey, LG light green, CC clear heat-reflecting, CD clear heat-reflecting + acoustic, GA green acoustic, BA blue acoustic, YA grey acoustic, YS grey solar, ZS bronze solar, CB/GB/BB heat-absorbing, AS/AB clear laminated 3/5-layer [6][7][8] |
| 8–9 | **Shade band colour** (windscreen only, optional) | BL blue, GN green, GY grey, BZ bronze, LG light green, YD dark grey [6][7][8] |
| next | **Body type + doors** (side and rear glass) | H hatchback, S saloon, C coupé, E estate, V van/MPV, P pickup, T tourer/cabrio, L lorry, R "ranger" (off-road?) **(meaning of R unverified)**. Followed by a door count, e.g. H3, H5, S4, E5 [7] |
| next | **Side-glass location** | FD front door, FQ front quarter, FV front vent, RD rear door, RQ rear quarter, RV rear vent [7] |
| next | **Features / equipment** (one letter each) | A antenna, B right-hand drive, C camera / lane-warning / ACC area, G GPS, H heated, I other equipment, J TV antenna, L/R left/right half, M rain and/or light sensor, N water-repellent coating, O fog sensor, P silkscreen change for sensors (LDW, night vision, city brake), U head-up display, V VIN window, W additional equipment / moulding, Z encapsulation [6]. Polish sources give the same C/H/M/U and "VW" for moulding/seals [5] |
| last (2 → 3 chars from 2025) | **Modification**: digit + letter | The letter names what changed (A thickness, B silkscreen, C mirror mount, D encapsulation, E heating, F antenna, G VIN window, H mirror position, T sensor…). Digits 1–5 and 6–9 select different combinations. "Z" means the code needed more than 15 characters [6] |

**What the vehicle number really is.** It is a reference number for the model the glass was **created** for. Twin models sold under other brands share the same glass, and so share the same code [3]. Real data also shows that one make spans **several leading blocks**. Toyota appears as 83xx (Avensis 8305, 8346, 8379; Aygo 8363; Prius 8385) and as 84xx (Auris 8409, Camry 8403) [10]. So "2 digits = make" is a convention, not a rule **(inference from [10])**.

### Decoded examples
| Code | Vehicle (from source) | Decoding |
|---|---|---|
| `5328AGNGNV` | Mercedes 124 saloon [7] | 53 Mercedes, 28 W124 · A windscreen · GN green · GN green band · V VIN window [7] |
| `3566AGSHMVW7X` | Ford Focus II [6] | 35 Ford, 66 Focus II · A · GS green solar · H heated · M rain sensor · V VIN window · W moulding · 7X modification (silkscreen) [6] |
| `5428AGNGY` | Mercedes Vito W638, 1996– [8] | A · GN green · GY grey band [8] |
| `5438AGSA` | Mercedes Vito, 2003–, "+antenna" [8] | A · GS green athermal · A antenna [6][8] |
| `8379AGACHMPZ2V` | Toyota Avensis III T27, 11/2008– [10] | A · GA green acoustic · C camera · H heated · M sensor · P sensor silkscreen · Z encapsulation · 2V modification (decoded by applying [6]) |
| `8409AGAMZ` | Toyota Auris, 2012– [10] | A · GA green acoustic · M sensor · Z encapsulation (decoded by applying [6]) |
| `8409LGNH5FDW` | Toyota Auris, 2012–, side window [10] | L left · GN green · H5 hatchback 5-door · FD front door · W (additional equipment/moulding?) (decoded by applying [6][7]; **W unverified for side glass**) |
| `8409BGSHXZ` | Toyota Auris, 2012–, rear window [10] | B rear · GS green solar · H probably heated or hatchback · X ? · Z encapsulation? **(partly unverified)** |

**Caveats.** Codes for the same car can differ by **a single character**, so codes must be compared in full [3]. Several sources give inconsistent tables, for example CC as "clear" in one and "clear heat-reflecting" in another [6][7]. A system should therefore treat the Eurocode as an **opaque identifier** and should not parse meaning out of it.

### Not to be confused with the markings on the glass ("bug")
- The etched marking is a **regulatory** mark. It carries the "E"-in-circle plus a country number and an approval number under UN R43 (E1 Germany, E4 Netherlands, E20 Poland…) [11][12]. It may also show a Roman numeral for the glass type, e.g. II = laminated windscreen [11][9].
- UN R43 requires **every piece to bear the trade name or mark of the manufacturer** and the approval mark [12].
- The US equivalent is "DOT" plus an NHTSA-assigned manufacturer code, under FMVSS 205 [13].
- An optional manufacturer **M-code** (e.g. M203) encodes thickness and tint [9].
- Date dots and a year digit give the production month and year, but there is **no common standard** for them [9][8].

## 2. What a Eurocode does not tell you, and how it relates to other numbers

### Not carried by the Eurocode
- **Producer/brand of the physical piece.** The Eurocode identifies a glass *specification*. The manufacturer is identified by the trade mark and E-approval number etched on each piece [12][9]. Two pieces with the same Eurocode can come from different producers (OEM supplier vs aftermarket). *Inference: no source states this in so many words, but it follows from ARGIC being a cross-industry trade reference [1][3].*
- **OEM vs OEE vs aftermarket status.** OEM glass is made under the carmaker's licence and usually carries its logo. OEE ("original equipment equivalent") and other aftermarket glass do not [14]. Trade guides treat this as a separate dimension from the code [3].
- **Production date of the piece.** It is etched on the glass, not in the code [9].
- **Condition, damage, location, photos.** These are not part of any catalogue code. *(Self-evident; no source needed.)*
- **The exact vehicle.** The leading number is the model the code was *created* for, and twins share codes [3]. Fitment needs a separate list (§3).

### Relationship between numbers
- **OE (carmaker) part number vs Eurocode.** The OE number is the carmaker's own part number. The ARGIC code is the trade reference used by independent distributors. A UK lookup service recommends **holding both and checking that they describe the same glass** [3].
- **Cardinality is many-to-many in practice:**
  - One OE number can expand into several catalogue entries (heated/non-heated, trim, left/right) [15].
  - OE numbers are **superseded** over time, which creates replacement chains [15][16].
  - Manufacturers' own article numbers, e.g. Pilkington item numbers, are listed per OE number, and several brand items can map to one OE number [17] (retailer page, 403 on fetch; **content verified only via search snippet**).
- **North America:** NAGS (Mitchell) numbers play the Eurocode role. They use prefixes such as FW = foreign windshield, DB = domestic back glass, FD = door, FQ = quarter, and they have a fixed abbreviation list for features [18].
- **"Alternative" / interchangeable codes:**
  - The Eurocode position-5 table includes **C = "alternative windscreen"** [6]. That hints ARGIC has a notion of an alternative variant of a windscreen **(meaning not documented publicly; unverified)**.
  - VIN lookup services return "**all candidate parts**, with what distinguishes them" [19], and US VIN tools return "interchangeable parts" [20].
  - In the used-parts world, interchangeability is formalised by **Hollander Interchange numbers**. They have a 3-digit part type, a 5-digit number and an optional letter. Parts sharing the 5-digit number are *partially* interchangeable, and parts sharing the full number are *fully* interchangeable [21].
  - I found **no public ARGIC "alternative Eurocode" cross-reference table** **(unverified whether one exists in the member Matrix)**.

## 3. Vehicle fitment

- **Glass catalogues list fitment as rows: Eurocode → make, model (with generation), production years, glass type.** Example: Pilkington Poland's price list has the columns *No., Eurocode, Make, Model, Production year, Glass type*. A row looks like `8379AGACHMPZ2V | Toyota | Avensis III T27 | (11/2008-) | windscreen` [10].
- **TecDoc (TecAlliance), the European IAM standard:**
  - It combines manufacturers' article data with standardised **vehicle and reference data**.
  - "Vehicle linkages show which vehicles an article is suitable for."
  - OE and reference numbers allow finding "corresponding or comparable spare parts".
  - Search works by make/model, vehicle type, year, engine, or VIN/chassis number [22].
  - The vehicle-type id (**KType**) is used by marketplaces. For example, eBay DE/UK/FR/IT/ES/AU accept `KType` as the compatibility key, while US-style listings use Year/Make/Model name-value pairs [23].
  - The full TecDoc data format (v2.7, 207 pages) is public but was not text-extractable here [24] **(details of generic articles and criteria unverified)**.
- **ACES/PIES (Auto Care Association, North America):**
  - ACES is "the aftermarket industry data standard for the management and communication of product **fitment** data".
  - PIES covers product **information**.
  - Both validate against subscription reference databases: VCdb (vehicle configurations), PCdb (part categories), Qdb (qualifiers) and PAdb (part attributes) [25].
- **Marketplace fitment:** Allegro has a "Pasuje do" (fits) compatibility section that sellers manage through the API [26]. eBay attaches compatibility to items via KType/ePID or Year/Make/Model [23].
- **VIN-based lookup is common for glass, but a VIN alone is not enough:**
  - A VIN has a WMI, a VDS whose use is manufacturer-specific, and a VIS that only *may* encode options [27].
  - A UK glass lookup service: "a VIN is an identity code, not a list of every option fitted" [28].
  - The same model ships with several windscreens because of factory options (heated, acoustic, solar, HUD, camera, rain sensor), LHD/RHD, body and trim, and **mid-life production changes**. Build date matters more than model year [28].
  - Services resolve VIN → part number + Eurocode from "the manufacturers' own specified fitment data" (build/option data) and return multiple candidates when ambiguous [19][28].
  - Installers still verify against the glass bug and physical features [20][29].
  - **What VIN lookup needs:** access to carmaker build/option data, typically through a paid provider such as VINtoGlass (£2.20 per lookup, invitation-only) [19] or Autoglassportal [30]. A plain VIN decoder is not enough.

## 4. Data modelling in parts commerce: definition vs unit vs offer

| Layer | Holds | Evidence |
|---|---|---|
| **(a) Catalogue product / part definition** | identifier (ASIN, EAN/GTIN, Allegro product id, eBay ePID, Eurocode, OE number), attributes, fitment | Amazon Catalog Items API works "at the ASIN level, reconciled from various listings", and an ASIN is the same physical item across marketplaces [31]. Allegro offers point to a catalogue product in `productSet[].product` (by GTIN or product id), and offers for the same product are grouped [32][33] |
| **(b) Stock item / physical unit** | SKU, condition, quantity, location, photos, serial | eBay Inventory API: an **inventory item** is keyed by a seller SKU and holds product details, **condition and quantity**. **Inventory locations** are separate records [34]. schema.org `IndividualProduct` is "a single, identifiable product instance (e.g. with a particular serial number)", distinct from `Product`/`ProductModel` [35] |
| **(c) Offer / listing** | price, marketplace/channel, policies, status, seller | eBay: an **offer** adds price, marketplace, category, quantity and policies to an inventory item. `publishOffer` turns it into a **listing** with a listing id. Offers can be withdrawn and republished [34]. Amazon Listings Items API is the seller-specific layer keyed by SKU, holding offers and fulfilment availability [31] |

- **Many sellers, one product:** Amazon (one ASIN, many sellers' listings) [31] and Allegro (offers grouped under one product card) [32][33] both separate the shared catalogue product from per-seller offers. Fitment is attached to the product, or to the item on eBay [23].
- **Used/salvage parts, where each unit is unique:**
  - Recyclers key every part to an **interchange number**, the catalogue-level "kind of part". Hollander Interchange is "the foundation for each and every part look up in recycler inventory management systems" [36][21].
  - Each physical part then gets its **own stock number, mileage and grade**. On Car-Part.com, grades are A/B/C: mechanical parts are graded by mileage and body parts by damage codes, and inconsistent condition data leaves a part ungraded [37][38].
  - The listing shows the stock number, mileage and grade per unit [38].
  - So the used-parts pattern is: catalogue/interchange (a) → unique unit with quantity 1 (b) → listing on one or more channels (c).

## 5. Normalised dictionaries vs free text

| Attribute | Practice | Standardised code list? |
|---|---|---|
| Glass position/type (windscreen, rear, side L/R, door/quarter/vent, roof) | Dictionary | Yes, inside the Eurocode (position letter + FD/FQ/RD/RQ…) [6][7]. NAGS prefixes (FW, FB, FD, FQ, FR, FS, FV…) [18] |
| Colour/tint + shade band | Dictionary | Yes, Eurocode 2-letter colour and band codes [6][7]. The M-code last digit gives tint (0 clear, 1 green, 2 bronze, 3 blue) on some producers' glass [9] |
| Features/equipment (heated, acoustic, sensor, camera/ADAS, HUD, VIN window, antenna, encapsulation) | Dictionary, multi-valued | Yes, Eurocode feature letters [6]. NAGS abbreviation list (HUD, Htd, LDWS, Ant, W/Bracket…) [18]. Glass pictograms (speaker = acoustic, wavy lines = heated) [29] |
| Body type + doors | Dictionary | Eurocode body letters + door count [7]. NAGS 2 Dr/4 Dr [18] |
| Producer/brand | Dictionary | Regulated identity marks (UN R43 trade name + approval number; US DOT code assigned by NHTSA) [12][13]. ACES/PIES has a shared **Brand Table** [25]. No European public brand code list found **(unverified)** |
| Glass construction (laminated/tempered…) | Dictionary | UN R43 Roman numerals I–VII [11] |
| Make/model/generation/years | Dictionary or reference DB | TecDoc vehicle types (KType) [22][23]. ACES VCdb [25] |
| Condition/grade | Dictionary (A/B/C) plus free-text notes | Car-Part.com grades [37]. No glass-specific standard found **(unverified)** |
| Notes, damage description | Free text | — |

ETIM is a general product-classification model: classes with **features** that are alphanumeric (from fixed value lists), logical, numeric, or range with units [39]. It is a reference pattern for "attribute + controlled values". No automotive-glass ETIM class was found **(unverified)**.

---

## Implications for a glass catalogue (neutral options, not decisions)

1. **Treat the Eurocode as an identifier.**
   - Option A: store it as an opaque, validated string (up to 17 characters, two formats coexisting), with features held as separate normalised attributes.
   - Option B: also parse it into components for search hints.
   - Risk of B: the tables are member-only and inconsistent in public sources, so derived meaning can be wrong.
2. **Choose the key of a catalogue item.** The options are Eurocode, OE number, or an internal id with Eurocode and OE number as (possibly many) cross-references. The evidence points to many-to-many, with supersession chains, among OE numbers, Eurocodes and producer article numbers.
3. **Decide where producer/brand and OEM/OEE status live.** They can be attributes of the physical unit, or a "catalogue item × producer" variant. The Eurocode does not carry them.
4. **Choose how to separate the three layers.**
   - Option A: catalogue glass (spec + fitment) → stock unit (condition, producer, date code, location, photos, often quantity 1 for used glass) → offer/listing (price, seller, channel, status). This is the eBay/Allegro/salvage pattern.
   - Option B: merge unit and offer when only one channel exists.
5. **Fitment granularity.** The options are make/model/generation + year range (as in glass price lists), a reference vehicle tree (TecDoc KType, ACES VCdb, both licensed), or own dictionaries. VIN lookup needs a paid build-data provider and still yields candidate lists, so it may sit alongside manual verification rather than replace it.
6. **Dictionaries.** Position, colour/band, features, body type, construction and producer can be closed dictionaries; features are multi-valued. They can optionally map to Eurocode letters or NAGS abbreviations for import/export. Condition notes stay free text.
7. **Alternatives/interchange.** This can be modelled explicitly (a link between catalogue items, as in Hollander's partial and full interchange) or left out. There is no public ARGIC alternative list to import.

---

## Sources
1. ARGIC — About us: https://www.argic.org/aboutus
2. AGC Automotive Replacement Glass — "The Eurocode® will evolve in 2025": https://www.agc-arg.com/en/node/1761 (also https://www.agc-arg.com/da/node/1763)
3. VINtoGlass — "ARGIC windscreen codes and Eurocodes explained": https://vintoglass.com/guides/argic-eurocode-explained
4. Trademarkia — "ARGIC Eurocode" EU trademark: https://trademark.trademarkia.com/ctm/argic-eurocode-011153772.htm
5. Glass-Pol — "Eurocode szyby samochodowej": https://www.glass-pol.pl/pl/blog/eurocode-szyby-samochodowej-co-musisz-o-nim-wiedziec-przed-zakupem-szyby
6. ProGlass (Moscow) — "Еврокод стекла" (full feature and modification tables): https://proglass-msk.ru/eurocode/
7. RemSteklo — "Как прочитать еврокод" (body/door/location tables): http://remsteklo.ru/eurocode.html
8. Szyby samochodowe Szczecin — "Oznaczenia szyb samochodowych w systemie EUROCODE": http://szyby-samochodowe-szczecin.blogspot.com/2012/09/oznaczenia-szyb-samochodowych-w.html
9. Carglass Estonia — "Automotive glass marking": https://carglass.ee/en/carglass/automotive-glass-marking-2/
10. Pilkington Automotive Poland (markoweszyby.pl) — Toyota price list: https://www.markoweszyby.pl/cennik-i-asortyment/szyby-samochodowe-toyota/
11. AGRR Magazine (Beranek) — "European Codes": https://beranek.agrrmag.com/2013/04/european-codes/
12. UNECE Regulation No. 43, Rev. 4: https://www.unece.org/fileadmin/DAM/trans/main/wp29/wp29regs/2017/R043r4e.pdf
13. 49 CFR 571.205 (FMVSS 205): https://www.law.cornell.edu/cfr/text/49/571.205 ; NHTSA interpretation: https://www.nhtsa.gov/interpretations/10181
14. Speedy Glass — OEE vs OEM: https://www.speedyglass.ca/en/driver-tips/technologie/les-differences-entre-un-pare-brise-oee ; Fleet News (OEE/OEM ADAS): https://www.fleetnews.co.uk/news/fleet-industry-news/2019/08/08/oee-and-oem-glass-offers-same-adas-functionality-says-new-research
15. T1A Auto — "How to cross reference parts": https://www.t1aauto.com/blogs/t1-auto-blogs/cross-reference-parts
16. prodevelop — Part usage / OE replacement chains: https://www.prodevelop.de/en/solutions/part-usage/
17. Buycarparts — OE 811675 (Pilkington items per OE number; snippet only, page 403): https://www.buycarparts.co.uk/oen/811675
18. Mitchell — NAGS abbreviations: https://www.mymitchell.com/tchs/helpfiles/mcg_pos/1033/Content/42225.htm
19. VINtoGlass — home: https://vintoglass.com/
20. BidClips — VIN lookup feature overview: https://support.bidclips.com/knowledge/vehicle-identification-number-vin-lookup-feature-overview
21. iSoftData wiki — Hollander Interchange: https://wikido.isoftdata.com/index.php/Hollander_Interchange
22. Prodexa — "What is TecDoc?": https://prodexa.com/en/kb/tecdoc/
23. eBay Trading API — ItemCompatibilityType: https://developer.ebay.com/DevZone/XML/docs/Reference/eBay/types/ItemCompatibilityType.html ; Inventory API product compatibility: https://www.developer.ebay.com/api-docs/sell/inventory/resources/inventory_item/product_compatibility/methods/getProductCompatibility
24. TecAlliance — TecDoc Data Format v2.7: https://a.storyblok.com/f/297549/x/9ae209aefb/tecdoc-data-format_version_2-7_en_2-0-33.pdf
25. Auto Care Association — Standards: https://autocare.org/standards/
26. Allegro developer — "Jak zarządzać sekcją Pasuje do": https://developer.allegro.pl/compatibility_list
27. Wikipedia — Vehicle identification number: https://en.wikipedia.org/wiki/Vehicle_identification_number
28. VINtoGlass — "Why the same car can need different windscreens": https://vintoglass.com/guides/why-the-same-car-needs-different-windscreens
29. Fleet Motorglass — OEM glass, sensors, HUD and codes: https://www.fleetmotorglass.co.uk/oem-glass-matching-sensors-hud-codes/
30. Autoglassportal — Eurocode and part number from VIN: https://autoglassportal.com/
31. Amazon SP-API — Catalog Items API v2022-04-01: https://developer-docs.amazon.com/sp-api/docs/catalog-items-api-v2022-04-01-reference ; Listings APIs FAQ: https://developer-docs.amazon.com/sp-api/docs/listings-apis-faq
32. Allegro developer — offer assigned to a product (productSet): https://developer.allegro.pl/tutorials/list-offer-assigned-product-one-request-D7Kj9M71Bu6
33. Lengow help — Allegro (grouping offers per product): https://help.lengow.com/hc/en-us/articles/360021413632-Allegro
34. eBay Inventory API — overview: https://developer.ebay.com/api-docs/sell/inventory/overview.html ; inventory item to offer: https://developer.ebay.com/api-docs/sell/static/inventory/inventory-item-to-offer.html ; managing inventory and offers: https://developer.ebay.com/api-docs/sell/static/inventory/managing-inventory-and-offers.html
35. schema.org — IndividualProduct: https://schema.org/IndividualProduct
36. Auto Recyclers of Canada — "An introspection on interchange": https://autorecyclers.ca/an-introspection-on-interchange/
37. Car-Part.com Scoop newsletter (part grading), 2008: https://products.car-part.com/newsletters/scoop/cps20080319.html ; buyer FAQ: https://car-part.com/buyerfaq.htm
38. URG recycler demo listing (stock no., mileage, grade): https://demo11.autopartsearch.com/parts/CADILLAC/ESCALADE/930P/2016
39. ETIM International Guidelines V3 2020: https://www.etim-international.com/wp-content/uploads/2019/07/ETIM-International-Guidelines-V3-2020.pdf
