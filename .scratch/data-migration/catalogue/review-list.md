# Catalogue — pre-migration review list

- **Generated:** 2026-11-11 from the production dump `allglass_v1_prod` (aggregate reads; no personal data). Regenerate before the import, because V1 is still in use.
- **Purpose:** the doubtful V1 records the client settles **before** the import (D-13, D-35, D-40, D-45, D-47, P1–P6). Every fix is made **in V1 itself** (V1 panel or a one-off update on the V1 database), so the import runs on clean data and needs no review tooling. Where V1 has no field for the answer (section C), the answer goes into the import's small mapping file.
- **If an item is left unsettled**, the import applies the default named in its section and lists the item in its run report.

## A. Groups whose codes differ beyond the vehicle prefix (27)

- 2723AGNBL1B · 6520AGNBL2B
- 2724LGNV4RQO · 6537LGNV4RDOW
- 2737AGNCMVWZ1L · 3750AGNCMVWZ1L · 6353AGNCMVWZ · 6552AGNCMVWZ1L
- 2737AGSVWZ1C · 3750AGSVWZ1C · 6353AGSVZ01C · 6552AGSVWZ1C
- 2750AGNMV1B · 5689AGNMV2B · 6565AGNMV1B
- 2763AGACHMVZ · 3378AGRCHMVZ · 6348AGRCHMVZ · 6573AGRCHMVZ · 8439AGRCHMVZ
- 2770AGNCMVZ1D · 6357AGNCMVZ01K
- 3014RGSH5FD · 3022RGSS4FD
- 3550RGNV5RQW · 3550RGSV5RQW · 7606RGNV5RQW · 7606RGSV5RQW · 8548RGNV5RQW · 8548RGSV5RQW
- 3581AGACHMV2P · 3581AGACHMVZ3P
- 3743LGNV2FD · 3743LGSV2FD
- 3960RGN4FDW · 3960RGNS4FDW
- 3998AGNV · 3998AGSV
- 4017AGNV1P · 4017AGNVH1P
- 5337RBNE5RQ · 5337RBSE5RQ
- 5351LGNS4FD · 5351LGSS4FD
- 5351LGNS4RD · 5351LGSS4RD
- 5399AGACMVZ1L · 5399AGACMVZ3L
- 6302AGNBLVZ1Q · 6302AGNBLVZ6I
- 6302AGNVZ1Q · 6302AGNVZ6I
- 7223LCLV2FV · 7223RCLV2FV
- 7249AGNV · 7249AGSV1M
- 7604RGNH5RD · 7609RGNS4RD · 8549RGNS4RD
- 7816AGAVWZ · 7816AGAVWZ1B
- 8374BGNHW3E · 8374BGSHW1E
- 8642AGACMVZ · 8642AGACMVZ1B
- FW03241 · FW3198AGNBLHM · FW3241

## B. Groups with conflicting parameters on live offers (17)

- **3775AGAV1C · 6098AGAV1C · 6340AGAV1C · 7296AGAV1C** — equipment: (none) ×1 | AKUSTYCZNA ×1
- **8374AGAW** — equipment: (none) ×1 | AKUSTYCZNA ×1
- **4447AGABLHIMOVW** — equipment: AKUSTYCZNA + CZUJNIK DESZCZU/ŚWIATŁA + CZUJNIK PAROWANIA + OGRZEWANIE + PAS PRZYCIEMNIAJĄCY + POLE VIN ×1 | AKUSTYCZNA + CZUJNIK PAROWANIA + OGRZEWANIE WYCIERACZEK + PAS PRZYCIEMNIAJĄCY + POLE VIN ×1
- **8618AGAMVZ1W** — equipment: AKUSTYCZNA + CZUJNIK DESZCZU/ŚWIATŁA + POLE VIN ×2 | POLE VIN ×1
- **8658ACKCKMVZ** — colour: ZIELONA ×1, POWŁOKA GRZEWCZA ×1
- **7623ACKCKMVZ1L · 8627ACKCKMVZ1L** — colour: SOLAR ×1, ZIELONA ×1; equipment: AKUSTYCZNA + OGRZEWANIE POWŁOKĄ + POLE VIN + WSPORNIK KAMERY ×1 | AKUSTYCZNA + CZUJNIK DESZCZU/ŚWIATŁA + OGRZEWANIE POWŁOKĄ + POLE VIN + WSPORNIK KAMERY ×1
- **3583AGAHMVZ6P** — equipment: AKUSTYCZNA + OGRZEWANIE + POLE VIN ×1 | AKUSTYCZNA + CZUJNIK DESZCZU/ŚWIATŁA + OGRZEWANIE + POLE VIN ×1
- **4457AGNCIMOV1L** — equipment: AKUSTYCZNA + CZUJNIK DESZCZU/ŚWIATŁA + CZUJNIK PAROWANIA + POLE VIN + WSPORNIK KAMERY ×1 | CZUJNIK DESZCZU/ŚWIATŁA + CZUJNIK PAROWANIA + POLE VIN + WSPORNIK KAMERY ×1
- **7824ACKCKMVZ** — colour: SOLAR ×4, POWŁOKA GRZEWCZA ×1
- **4458AGSCHIMOV** — equipment: CZUJNIK DESZCZU/ŚWIATŁA + CZUJNIK PAROWANIA + POLE VIN + WSPORNIK KAMERY ×3 | CZUJNIK DESZCZU/ŚWIATŁA + OGRZEWANIE + POLE VIN + WSPORNIK CZUJNIKA PAROWANIA + WSPORNIK KAMERY ×1
- **8568AGSVZ1H** — equipment: POLE VIN ×1 | PAS PRZYCIEMNIAJĄCY + POLE VIN ×1
- **7820ACKCKMVZ1L** — colour: ZIELONA ×2, POWŁOKA GRZEWCZA ×2
- **3367AGACHMVWZ** — position: TYLNA ×1, PRZEDNIA ×1
- **7819AGAMVZ** — equipment: AKUSTYCZNA + CZUJNIK DESZCZU/ŚWIATŁA + POLE VIN ×1 | AKUSTYCZNA + CZUJNIK DESZCZU/ŚWIATŁA + POLE VIN + WSPORNIK KAMERY ×1
- **8626ACKCKMVZ1L** — colour: ZIELONA ×2, SOLAR ×1
- **5385AGAACMVZ2L** — equipment: AKUSTYCZNA + ANTENA + CZUJNIK DESZCZU/ŚWIATŁA + POLE VIN + WSPORNIK KAMERY ×2 | AKUSTYCZNA + ANTENA + CZUJNIK DESZCZU/ŚWIATŁA + POLE VIN + SYSTEM NIGHT VISION + WSPORNIK KAMERY ×1
- **6324AGSVWZ** — equipment: (none) ×2 | POLE VIN ×1

## C. Eurocodes with no offer at all — position needed (45)

V1 has no position for these (it lived on offers). Suggestion = the ARGIC position letter (5th character: A windscreen, B rear, L/R side…), only a hint. Default: the suggestion where there is one; a code with no suggestion and no answer is **not migrated** and is listed in the run report. Several of these look like OEM numbers (see D).

- 09113346 — suggested: side/other (3)
- 1052530 — suggested: side/other (5)
- 1052532 — suggested: side/other (5)
- 1348859 — suggested: side/other (8)
- 1K4845202B — suggested: side/other (4)
- 2027200318 — suggested: side/other (2)
- 2056709000 — suggested: side/other (7)
- 2457AGSMV1T — suggested: PRZEDNIA
- 2760AGSCMVZ — suggested: PRZEDNIA
- 2K5845306BNVB — suggested: side/other (4)
- 3361BGPHBZ — suggested: TYLNA
- 3566AGAHMVW7X — suggested: PRZEDNIA
- 3743LGSV2RQOW — suggested: side/other (L)
- 4336AGSMOVZ — suggested: PRZEDNIA
- 46407734 — suggested: side/other (7)
- 46512023 — suggested: side/other (2)
- 46831889 — suggested: side/other (1)
- 51314A16386 — suggested: side/other (4)
- 51317316799 — suggested: side/other (7)
- 5168AGAM1V — suggested: PRZEDNIA
- 5698AGNMVW95 — suggested: PRZEDNIA
- 5H0845011ATNVB — suggested: side/other (4)
- 6252RCLE5RQZ — suggested: side/other (R)
- 6277BGNSA — suggested: TYLNA
- 6294LGSE5RQZ1D — suggested: side/other (L)
- 6345AGSCMVZ — suggested: PRZEDNIA
- 7608LGSS4RVZ — suggested: side/other (L)
- 7640415 — suggested: side/other (4)
- 7640416 — suggested: side/other (4)
- 7677698 — suggested: side/other (6)
- 8116W5 — suggested: side/other (W)
- 8558RGNH3RQZ — suggested: side/other (R)
- 85626500014 — suggested: side/other (6)
- 8568AGSGYMVZ1P — suggested: PRZEDNIA
- 8577LYPV5RV — suggested: side/other (L)
- 8611AGAMVWZ — suggested: PRZEDNIA
- 8621AGSGYAMVZ1T — suggested: PRZEDNIA
- 8832RGSS4FD — suggested: side/other (R)
- 9017250109 — suggested: side/other (2)
- 920159 — suggested: side/other (5)
- 96255772 — suggested: side/other (5)
- 96649119 — suggested: side/other (9)
- AD39AGNBL — suggested: PRZEDNIA
- GRL163900A9D — suggested: side/other (6)
- TEST123456 — suggested: side/other (1)


Defaults for A and B: A — the group is joined as found (fix in V1 by removing the wrong eurocode from the offer); B — the most frequent value among live offers, ties to the newest offer (fix in V1 by correcting the offers).

## H. Joins made only through deleted offers (3)

Applied by the import (P1); listed so the client can split any that are wrong.

- 2724LGNV4RQO · 6537LGNV4RDOW
- 2737AGSVWZ1C · 3750AGSVWZ1C · 6353AGSVZ01C · 6552AGSVWZ1C
- 8374BGNHW3E · 8374BGSHW1E

## D. Codes that do not look like a standard Eurocode (70)

Pattern checked: four digits then two letters. Many of these are real (new ARGIC codes with a letter prefix such as `AJ18…`, maker codes such as `DW02075GTY`); numeric or carmaker-shaped ones (`1K4845202B`, `51314A16386`) are probably OEM numbers entered as eurocodes. For each: keep as Eurocode (default), move to the OEM codes of a named model, or drop. `TEST123456` is dropped (user, P3).

- with offers: 05018148AA, 105019706AA, 1317985, 3931B, 55176280, 8V7845099PNVB, 96255771, A223AGNBL, AD48AGSV, AF63AGRBLCHMV1Z, AF63AGRBLVWZ, AG13AGNW, AJ03AGNBL1P, AJ03LGNR5RQ, AJ03LGNR5RV, AJ06RGNR3FD, AJ07ASMRT, AJ10BYPROW, AJ10RYPR5RQZ1D, AJ18AGRCMOVW1T, AJ18AGSCMVW1B, AJ18AGSMVW1B, AJ18BYPROW, AJ19AGACIOVZ95, AJ21AGACHIMVW1Z, AJ21AGACIMVWZ, AJ22AGSVZ, AJ24AGACIMVZ, DB12981YPY, DW00724, DW02075GTY, DW02123AGN, DW02140GTY, DW02323, DW02324AGNMC, DW02498GBY, E406AGACMUVZ, EI05AGACMOVZ, EQ01AGNCMVZ, FW02581, FW03241, FW3198AGNBLHM, FW3241, K05017077AB
- without offers: 09113346, 1052530, 1052532, 1348859, 1K4845202B, 2027200318, 2056709000, 2K5845306BNVB, 46407734, 46512023, 46831889, 51314A16386, 51317316799, 5H0845011ATNVB, 7640415, 7640416, 7677698, 8116W5, 85626500014, 9017250109, 920159, 96255772, 96649119, AD39AGNBL, GRL163900A9D, TEST123456

## E. Producers (D-47)

- Probable duplicates: `BYD OEM` (1 offer) / `OEM BYD` (0); `LAMESON` (1) / `LEMSON` (0); `JAGUAR OE` (2) next to the "X OEM" pattern.
- Unused: `LEMSON`, `OEM BYD`, `PS`.
- Fix in V1: re-point offers to the kept value and delete the other. Default: migrated as they are.

## F. Vehicle bodies (P6)

The 68 raw values migrate trimmed. Proposed joins for the client to confirm (fix in V1 by editing `type` on the vehicles; default: no join):

- typos: `3 DRZWI HATCHACK` → `3 DRZWI HATCHBACK`; `5 DRWI HATCHBACK` → `5 DRZWI HATCHBACK`; `5 DZWI SUV SPORTBACK` → `5 DRZWI SUV SPORTBACK`; `KABIOLET` → `KABRIOLET`;
- same body, other spelling: `HATCHBACK 5D` = `HATCHBACK 5 DRZWI` = `5 DRZWI HATCHBACK`; `HATCHBACK 3D` = `3 DRZWI HATCHBACK`; `COUPE 2D` = `COUPE 2 D` = `2D COUPE`; `SEDAN 4D` = `4 DRZWI SEDAN`; `SUV 5D` = `SUV 5 DRZWI` = `5 DRZWI SUV`; `KOMBI 5D` = `5 DRZWI KOMBI`; `PICK-UP 4D` = `4 DRZWI PICK-UP`; `SAMOCHÓD CIĘŻAROWY` = `CIĘŻAROWY`; `2 DRZWIOWY` = `2 DRZWI`;
- not a body (generation or model typed into the body field): `1995-2003 W210`, `2000-2004 II`, `GT F07`, `JL`, `W460/W461/W463`, `XL30` — move to the vehicle's model or generation.
- ambiguous, left alone: `5 DRZWI` vs `HATCHBACK`/`SUV` variants, `OSOBOWY`, `VAN` variants.

## G. Position "Bez kategorii" (D-45)

One offer uses it and it has no eurocode link, so no Glass model is affected; the offer is an Offers migration matter (identification hint).
