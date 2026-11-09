# Domain model: Glass vs Offer vs catalogue

- **Started:** 2026-11-09
- **Prompt:** `.scratch/vehicles/domain-grilling-prompt.md`
- **Inputs:** `.scratch/vehicles/discovery/discovery.md`, `.scratch/offers/discovery/discovery.md` (both `ready-for-grilling`)
- **State:** rounds 1–4 closed, **done 2026-11-09** — D-01–D-28, ADR 0008–0010, GLOSSARY updated, roadmap revised; next: `.scratch/domain/handoff-catalogue-offers.md`, waiting for the user

## Extra facts checked in this session

Production dump `allglass_v1_prod`, aggregate only (2026-11-09):

- Eurocode links per non-deleted offer: 0 → 12, 1 → 1638, 2 → 64, 3 → 29, 4 → 7, 5 → 7, 6 → 1. So 108 offers (≈ 6%) carry alternative eurocodes.
- Eurocodes: 1817, of which 1368 have at least one OEM code; 1783 have a vehicle fitment (2827 fitments over 1188 cars).

## Proposed round order

1. **Round 1 — the three layers and their names:** whether the model has a catalogue product (Glass), a piece and a listing, or merges piece and listing into the Offer; Polish/English names; what the Glass is keyed by. (Topics 1, 2, 6.)
2. **Round 2 — attribute placement and override:** Glass vs piece vs listing for type/position, colour, equipment, producer, year, condition, rating, OEM; override rule (Offers Q-20); glass without eurocode. (Topics 3, 2.)
3. **Round 3 — identity and relations:** eurocode/OEM as identifiers vs separate Glasses; interchange; fitment vs interchange; vehicles and VIN. (Topics 4, 5.)
4. **Round 4 — the seller's path and the offer's life:** what a seller creates, unknown code, approval, Submissions, Inquiry, Reservation, Sale as parts of the Offer's life. (Topics 7, 8.)
5. **Round 5 — dictionaries, module boundaries, integrations, plan.** (Topics 9, 10, 11, 12.)

## User input (2026-11-09, before round 1)

- The eurocode is a **catalogue identifier** (confirmed with another agent). The client strongly wants glass parameters assigned **by eurocode**.
- Why offers lack eurocodes: sellers often did not know the eurocode (it was not available to them); the old `offers.eurocode` field became "type any identifier, the system will recognise it". Then an admin by hand, or the system via OEM, found the eurocode or filled the data.
- Wanted split: **Szyba** = the physical thing; **Oferta** = a Szyba put up for sale with a price; the identifier is either a **preset of glass data** or a **catalogue identifier** — to decide.
- Term conflict noted: the prompt's hypothesis used "Szyba" for the catalogue definition; the user uses it for the physical piece. Resolved in round 1 (Q1).

## Round 1 (asked 2026-11-09)

Extra facts: `quantity > 1` on 16 offers in all — 11 "Nowa" (max 13), 5 "Używana" (max 4), 0 Outlet.

- Q1 Names: Szyba = physical piece; catalogue entry = Eurokod; Oferta = Szyba for sale. Rec: yes.
- Q2 Catalogue entry is a reference (offer reads parameters from it), not a preset (copied defaults). Rec: reference.
- Q3 One catalogue entry per eurocode, eurocode is its identity (normalised); OEM codes are lookup keys on it, not entries. Rec: yes.
- Q4 Szyba and Oferta: separate words, one aggregate (Szyba lives only inside its Oferta; withdrawal/return reactivates the same Oferta). Rec: yes.
- Q5 One Oferta = exactly one Szyba; quantity dropped; identical new pieces via duplicate; migration splits the 16. Rec: yes.

### Round 1 answers (2026-11-09)

- Q1: names yes, but **GlassModel** for the catalogue entry, because per the client one glass can have more than one eurocode (the reason for V1's "alternative eurocodes"). User asks: separate Eurocode and GlassModel?
- Q2: (a) reference — accepted → D-01.
- Q3: (a) with a question what "the catalogue has no entry without a eurocode" means → reopened as 1b because of the GlassModel answer.
- Q4: user asks whether we need an internal "Offer" at all: an admin pushes the glass to WooCommerce and e.g. OLX; a sale marked in the panel must mark it on every channel. → reasked as 1b.
- Q5: (a) accepted → D-02. Pallets of new glass were the idea, never happened.
- User pasted a chat answer: alternative eurocodes arise from (1) supplier spelling of the suffix, (2) the same glass in twin vehicle models (different 4-digit prefix), (3) superseded codes, (4) one-way compatibility ("fits", not "the same"); suggests a canonical glass with code aliases (kind + source).

Fact checked (prod, non-deleted offers, primary vs each alternative eurocode, 176 pairs):
- **157 (89%) differ only in the 4-digit vehicle prefix**, the rest of the code identical (e.g. `8613AGSCHMVZ1L` / `7814AGSCHMVZ1L`) → same glass in another model (pasted case 2);
- 18 share the first 6 characters and differ in the suffix (e.g. `5351LGNS4RD` / `5351LGSS4RD`) → supplier variant or a real feature difference (cases 1, 3, 4);
- 1 other.

## Round 1b (asked 2026-11-09)

- 1b-Q1 Eurocode belongs to exactly one GlassModel (many-to-one); one-way compatibility is not a Eurocode of the model but a relation between GlassModels (round 3). Panel keeps working "by eurocode". Rec: yes.
- 1b-Q2 A GlassModel has at least one Eurocode, one of them its main one (its name in the panel). Rec: yes.
- 1b-Q3 The internal record is the **Oferta** (one aggregate holding one Szyba); a channel's copy (WooCommerce product, OLX ad) is a **Publikacja**. Rec: yes.
- 1b-Q4 The Oferta is the single source of truth for status; a sale marked once ends every Publikacja. Rec: yes.

### Round 1b answers (2026-11-09)

- Q1, Q2 yes → D-03, D-04. Q4 yes, but the user's word is **advert** (as for Otomoto, now OLX) → D-05. No inbound channel: the panel only publishes; it is the source of truth.
- Q3 "yes, but wait": reopened. The user considers a seller's **own stock of pieces** ("katalog szyb użytkownika"; later maybe workshops) from which chosen pieces are put up as Offers, and asks whether we have only "Szyba" or "Szyba + Oferta". → Round 1c.

Fact: prod status counts — Aktywna 1409, Sprzedana 317, Usunięta 288, Archiwalna 23, Zarezerowana 5, Odrzucona 4; Szkic and Oczekuje 0. Correction: Oczekuje **is** used (every seller save, 1721 acceptances; 0 is only today's snapshot); Szkic, the "in the panel but not for sale" state, has no rows.

## Round 1c (asked 2026-11-09)

- 1c-Q1 One record (piece + sale terms) now — variant A — named Oferta; a seller's unlisted pieces are Offers in draft; a separate stock item (variant B) is postponed until there is a real need (workshop stock, relisting one piece many times); splitting is mechanical thanks to D-02. Rec: A.

### Round 1c answer (2026-11-09)

- A → D-06. The user's navigation idea, kept for the panel design (round 5 / module grilling): **Katalog** (admin: Glass models, vehicles, dictionaries) · **Magazyn** (later, with variant B) · **Oferty** (admin: main list + adverts per channel; seller: own offers and how they do on channels, with sub-tabs for reservations and inquiries).
- Reminder from the user: in V1 every seller's offer, with or without a eurocode, went to verification; the admin verified and accepted, and only then published to channels (round 4).

## Round 2 (asked 2026-11-09) — attribute placement

- 2-Q1 On the GlassModel: position (V1 "rodzaj"), colour/tint, equipment/features, vehicle fitment, OEM codes; the shop category is an integration mapping. Rec: yes.
- 2-Q2 On the Offer: producer, production year, condition, rating, photos, descriptions, price, seller, status. Rec: yes.
- 2-Q3 No override of GlassModel parameters on an Offer; a different tint/feature is a different GlassModel; piece-specific remarks go to condition/description. Rec: yes.
- 2-Q4 An Offer without a GlassModel has no glass parameters, only the seller's identification hint (any code, OEM, vehicle text) until a GlassModel is assigned. Rec: yes.

### Round 2 answers (2026-11-09)

- Q1 yes → D-07. Context: V1 took glass types from WooCommerce categories because the catalogue was first imported from WooCommerce; the panel is now the main catalogue.
- Q2 yes → D-08. Shipping stays out for now: Otomoto is gone, OLX unknown, the user will ask the panel admin how shipping per user should work.
- Q3 (a) → D-09. The user notes V1 tied parameters to the seller's form; V2 need not. Conflicts are listed before migration and sent to the admin to resolve (may be old or test offers).
- Q4 (a) → D-10.

## Round 3 (asked 2026-11-09) — identity and relations

Facts: 1325 distinct OEM codes (normalised), 33 of them on more than one eurocode (max 6). Cars: 1188, 54 brands; eurocodes per car 1–8 (511 cars have one).

- 3-Q1 Fitment hangs on the GlassModel (union of its Eurocodes' V1 fitment); no reason/source on a Eurocode for now. Rec: yes.
- 3-Q2 One-way "can replace" between GlassModels is not built now; recorded as a later relation. Rec: yes.
- 3-Q3 Migration: V1 alternative eurocodes of one offer join one GlassModel (transitively); the 18 suffix-different pairs and any parameter conflicts go to the admin's review list. Rec: yes.
- 3-Q4 An OEM code is a lookup key that may sit on several GlassModels; a lookup can return several candidates. Rec: yes.
- 3-Q5 Vehicles: fitment only through GlassModels (V1 `car_offer` dropped); the vehicle catalogue's structure is decided in the Vehicles module grilling. Rec: yes.
- 3-Q6 VIN is never stored on a GlassModel; it is a search input (later, through a provider) and may be part of a seller's identification hint (donor car). Rec: yes.

### Round 3 answers (2026-11-09)

- Q1 (a), Q2 (a), Q3 yes, Q4 (a), Q5 yes, Q6 yes → D-11–D-16.

## Round 4 (asked 2026-11-09) — the seller's path and the Offer's life

- 4-Q1 A seller picks a GlassModel by eurocode, OEM or vehicle (sees the catalogue, never other sellers' offers); not found → identification hint; no silent free-text matching. Rec: yes.
- 4-Q2 Only admins create and change GlassModels, Eurocodes, OEM codes and fitment; a seller never adds to the catalogue. Rec: yes.
- 4-Q3 Approval is a step in the Offer's life; an Offer cannot be approved without a GlassModel; the admin assigns it from the hint while approving. Which seller edits need re-approval → Offers grilling. Rec: yes.
- 4-Q4 No Submission concept: the approval queue is the Offers waiting for approval; what the seller changed comes from the Change history. Submissions is not a module. Rec: yes.
- 4-Q5 Reservation and Sale are parts of the Offer, not their own records with a life; a Sale records price, date and channel; a return ends the Sale and the Offer is for sale again. Rec: yes.
- 4-Q6 An Inquiry is a request to the seller to reserve their Offer for a buyer; accepting it makes the Reservation; it lives in Offers; its process details → Offers grilling. Rec: yes.

### Round 4 answers (2026-11-09)

- All yes → D-17–D-22. Q1: the catalogue search is a nice wizard step or slide-over — to design in the Offers grilling. Q4: the admin used tickets as information (a changelog) even unopened; a "waiting" tab is expected to be enough, and adding more later breaks nothing.

## Round 5 (asked 2026-11-09) — dictionaries, module boundaries, integrations, ADRs

Facts (prod): glass types 31 values, 24 used; 70 live offers are **not glass** but accessories with their own ARGIC eurocodes — seal (USZCZELKA SZYBY PRZEDNIEJ, 46, e.g. `2456ASMRT`), mouldings (LISTWA…, 22), frames (RAMKA…, 2). Colours 7 (BEZBARWNA, CIEMNA, CIEMNOZIELONA, NIEBIESKA, POWŁOKA GRZEWCZA, SOLAR, ZIELONA — two are coatings, not colours). Equipment 20 values. Producers 62.

- 5-Q1 Glazing accessories (seal, moulding, frame) are GlassModels too; their kind is a value of the position list. Rec: yes.
- 5-Q2 One module **Catalogue** (GlassModel, Eurocode, OEM, fitment, vehicles, glass dictionaries) instead of a separate Vehicles module; overrides the roadmap's carried decision "Vehicles is its own module". Rec: yes.
- 5-Q3 Contract: Offers keeps a GlassModel id and reads/searches through Catalogue's ModuleApi; Catalogue knows nothing of Offers; integrations react to Catalogue events to refresh Adverts. Rec: yes.
- 5-Q4 Dictionaries (position, colour, equipment, producer) are admin-editable lists in Catalogue, no approval workflow, no seller proposals; equipment is one list (OfferExtras dropped); value clean-up in the Catalogue grilling. Rec: yes.
- 5-Q5 Channel keys and mappings (WP ids, categories, meta keys, endpoints) live in the integration; no ADR (follows from the architecture). Rec: yes.
- 5-Q6 ADRs: 0008 GlassModel known by several Eurocodes, referenced by Offers; 0009 Offer is one physical piece with no stock item; 0010 the panel only pushes to channels. Rec: write all three.

### Round 5 answers (2026-11-09)

- Q1 not accepted as asked: an **accessory is its own thing in the catalogue** (needed for the WooCommerce integration), **assigned to a GlassModel**; the 70 V1 offers with accessory "types" are still Offers. → Round 5b.
- Q2 (a) → D-23. Q3 yes → D-24. Q4 yes → D-25. Q5 yes → D-26; the admin will want to see an entry's integration status (e.g. a producer's WordPress sync) on a second tab or section — for the grilling. Q6 yes → ADR 0009 and 0010 written; 0008 waits for 5b because accessories touch D-03.

## Round 5b (asked 2026-11-09) — accessories

- 5b-Q1 **Accessory model** (Model akcesorium): own Eurocode(s), a kind (seal, moulding, frame…), linked to one or more GlassModels it goes with; vehicles through those GlassModels. Rec: yes.
- 5b-Q2 An Offer refers to exactly one catalogue item: a GlassModel or an Accessory model; the rest of the Offer is the same. Rec: yes.
- 5b-Q3 A Eurocode belongs to exactly one catalogue item (GlassModel or Accessory model); codes unique across the catalogue. Rec: yes.
- 5b-Q4 Accessory kinds are their own list; accessory values leave the position list; selling a glass with its accessory as one Offer is not built now. Rec: yes.

### Round 5b answers (2026-11-09)

- The user (webmaster) knows of no client wish to extend the catalogue with accessories; asks how costly it is to add another kind of offered item later, and whether it fits the existing Offers module. If easy: record the preferred answers and keep the Accessory model as additional functionality to discuss with the client → D-27, ADD-A01. ADR 0008 written (D-03 unchanged).

## Round 6 (asked 2026-11-09) — roadmap and plan

- 6-Q1 Order: grill Catalogue → spec; grill Offers → spec; deliver Catalogue, then Offers. Rec: yes.
- 6-Q2 The discovery manager carries D-01–D-27 into both discoveries (decision logs, Q resolutions) and renames the Vehicles discovery folder to Catalogue then; this session does not touch discovery files. Rec: yes.
- 6-Q3 Roadmap edit as proposed in the chat (rows 5–9, data migration, carried decisions). Rec: apply.
- 6-Q4 Commit the domain files explicitly (GLOSSARY, ADR 0008–0010, this file, roadmap). Rec: yes.

### Round 6 answers (2026-11-09)

- All yes → D-28, roadmap updated, commit. The user adds: Catalogue does not exist in V1, so it needs **no discovery** but a module grilling on the two discoveries plus this model; then Offers grilling; then delivery, Catalogue first. Handoff for the next manager: `.scratch/domain/handoff-catalogue-offers.md`.

## Decisions

- **D-01** The catalogue entry is a **GlassModel**; an Offer **refers** to it and reads glass parameters from it; nothing is copied onto the Offer. — Why: the client assigns parameters by eurocode; parameters are constant per eurocode in 93–99.6% of groups; copying is the V1 defect. — re Vehicles ADD-01, Q-09, W-02, BR-06; Offers ADD-01, BR-38, Q-20 (override still open, round 2).
- **D-02** An Offer is **exactly one physical piece**; there is no quantity. Identical pieces are separate Offers (duplicate). Migration splits the 16 V1 offers with quantity > 1. — Why: 2030/2046 offers have quantity 1, no partial reservation or sale ever; the derived-stock status logic is V1's main defect there. — re Offers Q-06, BR-11, BR-12, BR-13, BR-25.
- **D-03** A **Eurocode belongs to exactly one GlassModel**; a GlassModel has one or more Eurocodes. Codes are unique across the catalogue, stored normalised (uppercase, no spaces, ≤ 17 characters). OEM codes hang on the GlassModel as lookup keys. One-way compatibility is a relation between GlassModels, not an extra Eurocode (round 3). V1's per-offer "alternative eurocodes" become Eurocodes of the GlassModel. — Why: 89% of V1 primary/alternative pairs are the same glass under another vehicle prefix; a code shared by models would be ambiguous. — re Vehicles BR-03, BR-05, Q-01, IMP-01, ADD-02; Offers BR-10.
- **D-04** A GlassModel always has **at least one Eurocode, exactly one of them main**; the main Eurocode names the model in the panel. No GlassModel exists from OEM codes alone. An Offer may still exist without a GlassModel (seller does not know the code; round 4). — Why: the client works by eurocode; keeps the V1 "by eurocode" workflow visible. — re Vehicles ADD-01, BR-05, Q-08; Offers Q-18.
- **D-06** For now there is **one record, the Offer**, describing exactly one physical Glass with its price, seller and status; a Glass does not exist without its Offer, and "have it but not selling yet" is a draft. A seller's **stock of unlisted pieces** (separate Glass + Offer) is a known direction, entered when workshops keep stock in the panel or one piece is listed repeatedly; the split is mechanical thanks to D-02. — Why: keeps the V1 admin flow; Szkic has 0 rows; no relisting seen. — re Offers Q-06, Q-14, BR-20, BR-25; Vehicles ADD-01.
- **D-07** The **GlassModel holds position, colour, equipment, vehicle fitment and OEM codes**. "Rodzaj szyby" becomes the glass **position**; a shop category is an integration mapping. — Why: what a Eurocode encodes (r05) and what is constant per eurocode (BR-38); the WooCommerce categories came from the original Woo import, and the panel is now the main catalogue. — re Vehicles Q-09, Q-14, IMP-06, BR-12; Offers BR-38.
- **D-08** The **Offer holds producer, production year, condition, rating, photos, descriptions, price, seller and status**. Shipping is out of the model for now (Otomoto gone, OLX unknown; to be clarified with the panel admin). — re Vehicles BR-16, Q-12; Offers BR-26, BR-34, BR-38.
- **D-09** An Offer **never overrides** a GlassModel parameter. Another tint, coating or feature is another GlassModel; piece-specific defects go to condition, rating and description. Before migration the V1 conflicts (6 colour, 16 equipment within one eurocode) are listed for the admin to resolve. — Why: a coating/feature is encoded in the eurocode; the conflicts are entry noise or real variants; overrides bring back two sources of truth. — re Offers Q-20, BR-38; Vehicles Q-09, W-02.
- **D-10** An Offer without a GlassModel has **no glass parameters**, only the seller's **identification hint** (any code, OEM number, vehicle text) until a GlassModel is assigned. Whether it may be approved or published before that is round 4. — re Vehicles W-03, BR-18, Q-08, Q-13; Offers BR-10, BR-34.
- **D-11** **Vehicle fitment hangs on the GlassModel** (the union of its Eurocodes' V1 fitment). A Eurocode carries no reason or source for now; both can be added later without changing the model. — re Vehicles BR-04, F-03, Q-02.
- **D-12** One-way compatibility ("GlassModel A can replace B") is **not built now**; it is a known future relation between GlassModels, never an extra Eurocode. — re Vehicles ADD-02.
- **D-13** Migration rule: Eurocodes that stood together on one V1 offer join **one GlassModel, transitively**. The 18 primary/alternative pairs that differ in the suffix and every group with conflicting parameters go to a **review list for the admin** (together with D-09). — re Vehicles BR-05, BR-08, G-02; Offers BR-38, Q-20.
- **D-14** An **OEM code is a lookup key that may belong to several GlassModels**; a lookup by OEM returns candidates. — Why: OEM ↔ Eurocode is many-to-many in the trade (r05); 33 V1 OEM codes sit on several eurocodes. — re Vehicles BR-03, Q-15, Q-16, IMP-05.
- **D-15** **Vehicles reach an Offer only through its GlassModel**; V1 `car_offer` is dropped (its 68 non-identical offers are folded in by migration or the admin). The vehicle catalogue's structure (free text vs make/model/generation) is decided in the Vehicles module grilling. — re Vehicles Q-02, IMP-02, BR-07, BR-17, ADD-03.
- **D-16** **VIN is never a GlassModel attribute.** It is a search input (later, through a paid provider; results are candidates confirmed by a person) and may be part of a seller's identification hint (donor car). V1 `offers.vim` is dropped. — re Vehicles Q-07, ADD-04.
- **D-17** A seller **picks a GlassModel from the catalogue** (search by eurocode, OEM or vehicle) and describes only the piece; if nothing fits, they leave an identification hint. No free-text code matched silently after save. Sellers see the catalogue, never other sellers' offers. The form shape (wizard step, slide-over) is for the Offers grilling. — re Offers ADD-01, Q-18, BR-33, BR-34, BR-10; Vehicles Q-08, IMP-04, BR-18, W-01.
- **D-18** **Only admins maintain the catalogue** (GlassModels, Eurocodes, OEM codes, fitment). Sellers never add to it, neither directly nor by proposals. — Why: a catalogue change changes every Offer that refers to it (D-01). — re Vehicles G-01, BR-10, BR-11, Q-06.
- **D-19** **Approval stays** as a step of the Offer's life (seller saves → waiting → admin accepts or rejects → active, publishable). **An Offer cannot be approved without a GlassModel**; the admin finds or creates it from the hint while approving. Which edits need re-approval → Offers grilling. — re Offers BR-20, BR-36, Q-02, Q-13, Q-17, F-06.
- **D-20** **There is no Submission concept.** The approval queue is the list of Offers waiting for approval; what a seller changed is shown from the Change history. The Submissions module is dropped from the roadmap. If the admin misses the informational feed, it can be added later without breaking anything. — re Offers Q-03, Q-04, Q-16, IMP-01, F-01–F-07; roadmap #7.
- **D-21** **Reservation and Sale are parts of the Offer**, not records with their own life. A Sale records the price at sale, the date and the channel; a return ends the Sale (kept in history) and the Offer is for sale again; releasing ends the Reservation. Details (who may release whose reservation, transitions) → Offers grilling. — re Offers Q-06, Q-08, Q-09, Q-15, IMP-02, IMP-03, IMP-04, BR-13–BR-15.
- **D-22** An **Inquiry is a request to the seller to reserve their Offer for a buyer**; accepting it makes the Reservation. It lives in Offers. Whether it records the requester and channel → Offers grilling. — re Offers Q-07, ADD-03, BR-17.
- **D-23** **One module, Catalogue** (Katalog): GlassModel, Eurocode, OEM codes, fitment, vehicles and the glass dictionaries. It replaces the roadmap's "Vehicles" module and overrides the carried decision "Vehicles is its own module". Split vehicles out only when they get a life of their own (VIN provider, reference vehicle tree). — re Vehicles B, ADD-03, ADD-04; roadmap #5.
- **D-24** **Contract Catalogue ↔ Offers:** an Offer stores only the GlassModel id; Offers reads the model summary (main eurocode, position, colour, equipment, vehicles) and searches candidates (eurocode, OEM, vehicle) through Catalogue's `ModuleApi`; Catalogue knows nothing of Offers. A GlassModel change is a domain event that integrations use to refresh Adverts. Deleting a model in use is blocked or done by merging (Catalogue grilling). — re Vehicles F-, Q-05; Offers BR-28, IMP-06.
- **D-25** **Position, colour, equipment and producer are admin-edited lists in Catalogue**, with no approval workflow and no seller proposals. Position is not a code enum (24 used values, growing); the seller's hard-coded four positions go. Equipment is one list (`OfferExtras` dropped; a closed ARGIC-lettered list is a later option). Producer is an Offer attribute but its list belongs to Catalogue. Value clean-up (coatings in colours, unused positions, deleting a value in use) → Catalogue grilling. — re Vehicles BR-10–BR-13, Q-10, Q-11, Q-14, G-09, ADD-05.
- **D-26** **Channel keys and mappings** (WordPress ids, shop categories, meta keys, endpoints) **live in the integration**; catalogue entries do not know channels. No ADR: it follows from the architecture. The admin will want to see an entry's integration status (e.g. producer synced to WordPress) on a tab or section — to design in the grilling. — re Vehicles IMP-06, Q-03, Q-14; Offers BR-28, BR-29.
- **D-27** **Accessories stay as they are in V1 for now:** a seal, moulding or frame with a eurocode is a GlassModel whose position is an accessory value; an Offer of it is an ordinary Offer. A separate Accessory model is **additional functionality** (ADD-A01) for the user to discuss with the client. — Why: no known client need; adding it later is cheap if Offers sees catalogue items only through Catalogue's `ModuleApi` summary (D-24), because the accessory models are then found by their position values and moved mechanically. — re Vehicles Q-14; 70 V1 offers (seal 46, mouldings 22, frames 2).
- **D-28** **Order of work:** Catalogue module grilling → Catalogue spec → Offers module grilling → Offers spec → Catalogue delivery → Offers delivery. Catalogue gets no new discovery: its inputs are the Vehicles discovery, the Offers discovery and this model. The discovery manager carries D-01–D-28 into both discoveries and renames `.scratch/vehicles/` to `.scratch/catalogue/`. — Why: Offers depends on Catalogue's `ModuleApi`, which is designed best knowing what Offers needs. — re roadmap #5–#7.
- **D-05** A channel copy of an offer (WooCommerce product, OLX ad, any future channel) is an **Advert**. The panel is the single source of truth: it publishes and updates Adverts, and **nothing is pulled back** from a channel — a sale elsewhere is marked by an admin in the panel and every Advert follows. Adverts belong to integrations, not to the domain. — Why: user decision; removes V1's import-driven sales and reservations. — re Offers BR-19, BR-28, IMP-06; Vehicles Q-02 (car_offer fed by the Woo import).

## Additional functionality (for the client)

### ADD-A01 Accessory model

- **What:** an Accessory model in Catalogue — own Eurocode(s), a kind (seal, moulding, frame…), linked to one or more GlassModels it goes with, vehicles inherited from them.
- **Preferred answers (round 5b, not decided):** an Offer refers to exactly one catalogue item, a GlassModel or an Accessory model, and is otherwise the same; a Eurocode belongs to exactly one catalogue item and is unique across the catalogue; accessory kinds are their own list and leave the position list; bundles (glass + accessory in one Offer) are not built.
- **Cost if added later:** low to moderate — a new catalogue entity, the Offer's reference widened to "catalogue item", a migration that moves GlassModels with accessory positions into Accessory models. Cheap only while Offers depends on the `ModuleApi` summary, not on the GlassModel type (D-24).
- **Why it may matter:** the WooCommerce shop may want accessories as their own product kind.

## Deferred to the module grillings

### Catalogue

- Vehicle catalogue structure: free text vs make → model → generation/years (Vehicles ADD-03, BR-01, BR-02, G-08).
- Dictionary clean-up: coatings among colours (POWŁOKA GRZEWCZA, SOLAR), unused and accessory positions, splitting "LISTWA … - LEWA" (D-25, D-27).
- Deleting or merging a Glass model or a dictionary value in use (Vehicles Q-10; D-24).
- Code normalisation details for Eurocodes and OEM codes (Vehicles Q-01, IMP-01).
- Who exactly may maintain the catalogue among admins (Vehicles G-01).
- An entry's integration status shown on a tab or section (D-26).
- ADD-A01 Accessory model, after the user talks to the client.
- Shipping methods (Vehicles Q-12): only if the panel admin wants them per user/glass.

### Offers

- Form shape: picking a Glass model as a wizard step or slide-over (D-17).
- Which seller edits need re-approval; bulk price change; duplicate (Offers Q-13, Q-17, IMP-05).
- Status model and guarded transitions (Offers Q-01, Q-06, Q-08, Q-14, IMP-02).
- Price and VAT at sale; seller's VAT status at listing time vs current (Offers Q-15, IMP-03).
- Who may release or sell whose reservation (Offers Q-09).
- Inquiry: requester and channel (Offers Q-07, ADD-03).
- Expiry (Offers Q-12, ADD-02).
- Journal and notifications (Offers Q-10, IMP-07); offer history view (Q-16).
- Shipping (after the user asks the panel admin).

## Plan

See `.scratch/domain/handoff-catalogue-offers.md` and `.scratch/roadmap.md`.
