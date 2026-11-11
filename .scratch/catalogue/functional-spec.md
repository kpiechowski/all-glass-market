# Catalogue — functional spec

<!-- The authoritative functional reference for implementing Catalogue in V2.
     It describes behaviour, not implementation: no classes, tables, patterns or framework terms,
     except inside legacy traces. Data is described as information the business needs, not as schema.
     Every requirement carries a trace: (F-/BR-/W- ids, rNN; D- decision). -->

- **Agreed:** 2026-11-11 · **Discovery:** `.scratch/catalogue/discovery/discovery.md` (written as "Vehicles"; inputs also `.scratch/offers/discovery/discovery.md` and `.scratch/domain/glass-offer-model.md`) · **Audit:** r06, findings applied 2026-11-11 · **V1 baseline:** `511ce3b`
- **Decision counts** (decisions touching Catalogue, D-01–D-58): preserve 4 · correct 27 · new 14 · exclude 7 · pending 1

## 1. Purpose and responsibilities

Catalogue is the reference of **kinds of glass** the marketplace trades in, and of the vehicles they fit. It answers "what glass is this?" so that an offer only has to describe the physical piece.

It owns:

- **Glass models**, each known by one or more Eurocodes, with OEM codes, position, colour, equipment and fitted vehicles;
- **Vehicles** and their **Fitment** to Glass models;
- the **dictionaries**: position, colour, equipment, producer, vehicle make and vehicle body;
- the **catalogue settings** that turn a Glass model into a thumbnail (shapes, fills, pictograms).

It does not own offers, their producer choice, approval, prices, photos or channels. Offers depends on Catalogue; Catalogue does not know Offers exists (D-23, D-24). Channel keys and mappings belong to the integrations (D-26). V1 had no catalogue: its eurocode register, car register and offer-level dictionaries are the material Catalogue replaces (discovery §B; D-23).

## 2. Domain concepts

Defined in `GLOSSARY.md`, section *Glass catalogue*: **Glass model**, **Eurocode**, **Main eurocode**, **Vehicle**, **Fitment**; used from other sections: **Identification hint**, **Approval**, **Advert**. Module-local terms (Polish UI labels come from the translation files at delivery):

- **OEM code** — a carmaker's part number recorded on a Glass model as a lookup key. Unlike a Eurocode, the same OEM code may sit on several Glass models (D-14).
- **Position** (V1 "Rodzaj szyby") — where the glass sits in the vehicle, e.g. PRZEDNIA, TYLNA OPUSZCZANA LEWA. An admin-edited list, not a fixed set; combined values are not split into position and side. Accessory kinds (seal, moulding, frame) are positions too (D-25, D-27, D-45).
- **Colour** (Kolor) — the tint of the glass; may also name a coating such as SOLAR (D-46).
- **Equipment** (Wyposażenie) — a feature of the glass, e.g. POLE VIN, OGRZEWANIE, HEAD-UP. A Glass model has any number.
- **Producer** (Producent) — maker of a physical piece. The list lives in Catalogue; the choice is made on an offer (D-25).
- **Dictionary value** — one entry of a dictionary: position, colour, equipment, producer, make or body.
- **Withdrawn** — a Glass model or dictionary value that can no longer be picked for new uses but stays shown wherever it is already used; it can be restored (D-44).
- **Thumbnail** — a generic drawing of a glass, shaped by the model's position and filled by its colour, with a row of equipment pictograms (D-54–D-56).
- **Review list** — the list of doubtful V1 records the client settles before the data migration (D-13, D-45, D-47).

## 3. Actors and permissions

| Actor | May | May not | Trace |
|---|---|---|---|
| Admin, Super Admin | Create, change, withdraw, restore and delete Glass models, Eurocodes, OEM codes, vehicles, fitment and dictionary values; edit the catalogue settings; browse and search the whole catalogue with offer counts | Delete an entry that is in use (FR-24) | D-18, D-42, D-44 |
| Seller (role User) | Search and browse Glass models in the picker of the offer form; read the summary of the Glass model of their own offer, also when that model is withdrawn | Open any catalogue screen; add or propose anything to the catalogue; see offer counts; find withdrawn models in the picker | D-17, D-18, D-42, D-44, D-52 |
| Offers module | Read summaries, search candidates, read dictionary lists, listen to catalogue events | Change catalogue data | D-24, D-48–D-51 |
| Integrations | Listen to catalogue events; add their own section to a catalogue entry's view | Store channel data in catalogue entries | D-26, D-43 |

## 4. Functional requirements

### Glass models

- **FR-01** [Correct] An admin can create a Glass model by giving its main Eurocode and its position; colour, equipment, OEM codes, further Eurocodes and vehicles are optional. V1 had only bare eurocodes, and glass parameters lived on offers. — (F-02, BR-03, BR-06; D-01, D-04, D-07, D-35)
- **FR-02** [Correct] An admin can change a Glass model's position, colour, equipment, OEM codes and vehicles, also while it is withdrawn. Every offer referring to the model shows the new values at once; nothing is copied onto offers. A withdrawn colour or equipment value already on the model stays on it when the model is saved; it cannot be newly chosen. — (F-06, F-17, W-02; D-01, D-09, D-44)
- **FR-03** [New] An admin can add a Eurocode to a Glass model, move a Eurocode to another Glass model, remove a Eurocode, and choose which of the model's Eurocodes is the main one (WF-02). — (F-19, BR-05; D-03, D-36, D-39)
- **FR-04** [Correct] An admin sees the Glass models as a list of full-width cards: thumbnail with equipment pictograms, main Eurocode, the other Eurocodes, OEM codes, position, colour and vehicle count, and a link "N offers" leading to the offer list filtered by that model. Cards are laid out so that search results can be compared side by side. A Eurocode has no screen of its own; it is managed only inside its Glass model. — (F-02; D-30, D-37, D-53, D-57)
- **FR-05** [Correct] An admin can search the list by a fragment of any Eurocode, OEM code or vehicle, and filter it by position, colour and vehicle make; withdrawn models are shown only on request. The search behaves as the candidate search (§9). — (F-02, F-05, Q-15; D-30, D-41, D-49)
- **FR-06** [New] An admin can withdraw a Glass model and restore it (FR-24). — (D-44)

### Vehicles and fitment

- **FR-07** [Correct] An admin can create and change a vehicle: make (from the make list, required), model, body (from the body list), production years from–to (to may be open), generation, ARGIC vehicle code (four digits), and a name. — (F-01, BR-01; D-31, D-34)
- **FR-08** [Correct] The vehicle's name is required, unique regardless of letter case, and editable. For a new vehicle the system proposes the name from the fields as `MAKE | MODEL | BODY | GENERATION FROM-TO | ARGIC code`, leaving out empty fields and writing an open end as `FROM-`; the admin may change it. The name is never rewritten by the system after that. — (BR-01; D-31, D-34)
- **FR-09** [Correct] An admin can list and search vehicles by name and fields, and see for each how many Glass models it fits (V1 showed offer counts through eurocodes). — (F-01, Q-02; D-15, D-34, D-44)
- **FR-10** [Correct] An admin can fit vehicles to a Glass model and remove fitments, from the Glass model. Fitment belongs to the Glass model, never to a Eurocode or an offer, and records no reason or source. — (F-03, BR-04, Q-02; D-11, D-15)
- **FR-11** [Correct] An admin can delete a vehicle; if it fits any Glass model, the admin is first warned how many, and deleting removes those fitments. Vehicles are not withdrawn. — (BR-07; D-44)

### Dictionaries

- **FR-12** [Correct] An admin can list, create, rename and delete values of the six dictionaries: position, colour, equipment, producer, vehicle make, vehicle body. A value has only a name and the withdrawn flag (FR-13). There is no approval step, no approval status and no proposal by sellers. — (F-12, F-13, BR-10, BR-11; D-18, D-25, D-31, D-32)
- **FR-13** [New] An admin can withdraw and restore a dictionary value (FR-24). — (Q-10; D-44)
- **FR-14** [Correct] Renaming a value takes effect wherever it is used. A renamed position, colour or equipment value announces every Glass model using it as changed; a renamed producer announces *Producer changed* (§9). — (D-44, D-51)

### Catalogue settings (thumbnail)

- **FR-15** [New] An admin can assign each position value one of the shipped glass shapes (windscreen, rear, side drop, side fixed/body, roof, accessory, unknown) and mark it mirrored (the shape flipped horizontally), for left and right variants. — (ADD-06; D-54)
- **FR-16** [New] An admin can assign each colour value any fill colour and an opacity (0–100%). Custom shapes cannot be uploaded. — (ADD-06; D-55)
- **FR-17** [New] An admin can assign each equipment value a pictogram from a shipped set. Changing any of these mappings changes no Glass model and announces nothing. — (ADD-06; D-56, D-57)
- **FR-18** [New] The system draws a Glass model's thumbnail from its position's shape (mirrored if set) filled with its colour's fill, and shows its equipment as a row of pictograms, each with a tooltip of the full value name. An unmapped position draws the "unknown" shape; no colour or an unmapped colour draws neutral clear glass; an unmapped equipment value shows a universal pictogram. — (ADD-06; D-54–D-56)

### Picking a Glass model (seller and approval)

- **FR-19** [Correct] A seller filling in an offer can open a Glass model picker: a search window showing candidates as cards with the thumbnail, pictograms, Eurocodes, OEM codes, position, colour and vehicles; choosing one sets the offer's Glass model. Withdrawn models are not shown. The rest of the offer form belongs to Offers. — (F-05, F-16, Q-08, IMP-04; D-17, D-52)
- **FR-20** [New] An admin approving an offer can search candidates with the search prefilled from the seller's identification hint, and can create a new Glass model in place (FR-01), with the main Eurocode prefilled from the hint (normalised by RULE-03) for the admin to confirm or correct; the new model is selected for the offer at once. The approval itself, including the rule that an offer cannot be approved without a Glass model, belongs to Offers. — (F-10; D-19, D-38)

### Services to other modules

- **FR-21** [New] Catalogue provides a Glass model summary for one or many models (§9). — (D-48)
- **FR-22** [New] Catalogue provides a candidate search (§9). — (D-49)
- **FR-23** [New] Catalogue provides the producer, position and colour lists for picking and filtering (§9). — (D-50)

### Removal

- **FR-24** [Correct] An entry (Glass model, dictionary value) can be deleted only while nothing uses it; an entry in use can be withdrawn instead. Withdrawn entries are not offered for picking or returned by search for new uses, but they stay visible with their names wherever already used, and can be restored. Uses inside Catalogue (a value on a Glass model or vehicle) are checked by Catalogue; a Glass model or producer referred to by any offer Offers still keeps, whatever that offer's state, counts as in use. Vehicles follow FR-11. — (Q-10, IMP-03, BR-07; D-24, D-44)

### Integration status

- **FR-25** [New] The view of a catalogue entry (e.g. a producer) has room for sections added by integrations, such as the entry's status, external id and last synchronisation on a channel; Catalogue itself stores and shows nothing about channels. — (IMP-06; D-26, D-43)

## 5. Business rules and constraints

- **RULE-01** Every Eurocode belongs to exactly one Glass model, and a Eurocode is unique across the catalogue, withdrawn Glass models included. — (BR-03; D-03, D-39)
- **RULE-02** A Glass model has at least one Eurocode and exactly one main Eurocode. All its Eurocodes are equal: any of them finds the model; the main one is only the label used on screens and adverts. — (BR-05; D-04, D-39)
- **RULE-03** A Eurocode is stored uppercase, letters and digits only: spaces and other characters typed are removed. After that it must have 1 to 17 characters, otherwise it is refused. — (Q-01, IMP-01; D-03, D-41, round 3 Q1)
- **RULE-04** An OEM code is stored as typed, uppercase and trimmed. Two OEM codes are the same when they match ignoring spaces, dashes and dots. A Glass model holds each OEM code once by that comparison; the same OEM code may sit on several Glass models. — (BR-03; D-14, D-41)
- **RULE-05** A Glass model always has a position. Colour is zero or one, equipment zero or more. — (BR-12; D-07, D-35)
- **RULE-06** An offer never overrides a Glass model's parameters; a different tint, coating or feature is a different Glass model. — (Q-09; D-09)
- **RULE-07** Only admins and super admins change catalogue data; sellers never add to it, directly or by proposal. — (G-01, BR-11; D-18, D-42)
- **RULE-08** A dictionary value's name is unique within its dictionary regardless of letter case. Names may contain any characters (V1's ban on `_` is dropped). — (BR-10, G-08; D-32)
- **RULE-09** A vehicle's name is unique regardless of letter case. Of its fields only the make is required; years from must not be after years to. — (BR-01; D-34)
- **RULE-10** Vehicles reach an offer only through the offer's Glass model. — (BR-17, Q-02; D-15)
- **RULE-11** VIN is never recorded in the catalogue. — (Q-07; D-16)
- **RULE-12** A change to a thumbnail mapping is not a change of any Glass model. — (D-57)

## 6. Workflows

### WF-01 Create a Glass model

- **Actors / trigger:** admin, from the Glass model list or from an offer's approval (FR-20).
- **Preconditions:** the actor is an admin or super admin.
- **Main flow:** 1. The admin enters the main Eurocode and picks a position. 2. Optionally adds colour, equipment, OEM codes, further Eurocodes and vehicles. 3. The system normalises the codes (RULE-03, RULE-04) and checks every Eurocode is free (RULE-01). 4. The model is saved as active.
- **Postconditions:** the model is findable by each of its Eurocodes, OEM codes and vehicles.
- **Alternative paths:** from approval, the search was prefilled from the hint; the new model is selected for the offer (FR-20).
- **Errors and exceptional scenarios:** a Eurocode already on another model → refused, naming that model; empty or over-long Eurocode → refused; no position → refused.
- **Trace:** (F-02, F-10; D-04, D-35, D-36, D-38)

### WF-02 Manage a Glass model's Eurocodes

- **Actors / trigger:** admin, on a Glass model.
- **Main flow:**
  - *Add:* the code is normalised; if it is free it joins the model, otherwise it is refused with a pointer to the model holding it.
  - *Move:* the admin moves one of the model's Eurocodes to another chosen model (active or withdrawn), where it joins as a non-main Eurocode.
  - *Change main:* the admin marks another of the model's Eurocodes as main.
  - *Remove:* the Eurocode leaves the model and the catalogue.
- **Postconditions:** RULE-01 and RULE-02 hold; each affected model is announced as changed.
- **Errors and exceptional scenarios:** moving or removing a model's last Eurocode → refused; moving or removing the main Eurocode → refused until another one is made main.
- **Trace:** (F-19, BR-05; D-36, D-39)

### WF-03 Change a Glass model's parameters

- **Actors / trigger:** admin, on a Glass model.
- **Main flow:** 1. The admin changes position, colour, equipment, OEM codes or fitment. 2. On save the model is announced as changed.
- **Postconditions:** every offer referring to the model shows the new data; integrations refresh adverts.
- **Trace:** (W-02; D-01, D-51)

### WF-04 Withdraw, restore or delete an entry

- **Actors / trigger:** admin, on a Glass model or a dictionary value.
- **Main flow:** 1. If nothing uses the entry, the admin can delete it. 2. If it is used, the admin can withdraw it; it disappears from picking and search for new uses. 3. A withdrawn entry can be restored.
- **Preconditions:** the actor is an admin or super admin.
- **Postconditions:** withdrawing or restoring a Glass model, and withdrawing a producer, is announced; withdrawing or restoring another dictionary value announces nothing (its Glass models do not change).
- **Errors and exceptional scenarios:** delete of an entry in use → refused, saying where it is used (inside Catalogue) or that offers use it.
- **Trace:** (Q-10; D-44, D-51)

### WF-05 Delete a vehicle

- **Actors / trigger:** admin, on a vehicle.
- **Main flow:** 1. The system shows how many Glass models the vehicle fits. 2. On confirmation the vehicle and its fitments are deleted. 3. Every affected Glass model is announced as changed.
- **Trace:** (BR-07; D-44, D-51)

### WF-06 Pick a Glass model

- **Actors / trigger:** seller in the offer form, or admin approving an offer.
- **Main flow:** 1. The user types a fragment of a Eurocode, OEM code or vehicle, optionally filtering by position, colour or make. 2. Candidates appear as cards ordered Eurocode hits first, then OEM hits, then vehicle hits, each saying what it matched. 3. The user picks one.
- **Alternative paths:** nothing fits → the seller leaves an identification hint on the offer (Offers); the approving admin can create a model (WF-01).
- **Errors and exceptional scenarios:** one OEM code giving several models → all are shown as candidates with the matched code.
- **Trace:** (F-05, F-16; D-14, D-17, D-38, D-49, D-52)

## 7. States and transitions

Glass models and dictionary values have two states:

| From | To | Who | Guard | Effect |
|---|---|---|---|---|
| (new) | Active | admin | WF-01 / FR-12 rules | — |
| Active | Withdrawn | admin | — | not offered for new uses; still shown where used; Glass model or producer: announced |
| Withdrawn | Active | admin | — | offered again; Glass model: announced |
| Active or Withdrawn | (deleted) | admin | not in use (FR-24) | gone; no announcement needed |

Vehicles have no states; they are deleted (WF-05).

## 8. Data requirements

- **Glass model:** its Eurocodes with the main one marked; OEM codes; position; colour (optional); equipment (set); fitted vehicles; withdrawn or active; creation and last-change dates.
- **Vehicle:** name; make; model; body; years from and to; generation; ARGIC vehicle code. Every field except name and make may be empty.
- **Dictionary value:** name; withdrawn or active (six dictionaries); no approval status.
- **Catalogue settings:** position → shape and mirrored; colour → fill and opacity; equipment → pictogram.
- **Kept for searching:** every Eurocode and OEM code, the vehicle name and fields.
- **Not kept:** slugs, approval statuses, channel keys and endpoints, VIN, per-offer eurocode strings, per-offer OEM, offer-to-vehicle links.

**Migration constraints** (detail in `.scratch/data-migration/catalogue/spec.md`, written in Phase 9):

- Eurocodes that stood together on one V1 offer join one Glass model, transitively; the V1 primary eurocode becomes main. Suffix-different pairs and groups with conflicting parameters go to the review list and are settled with the client before the migration runs. — (D-13, D-40)
- Position, colour and equipment of a model come from its live V1 offers, or from its deleted offers when it has no live one; models with no position after that go to the review list. — (D-09, D-35)
- Fitment of a model is the union of its Eurocodes' V1 fitment plus V1 offer-to-car links folded in. — (D-11, D-15)
- V1 offer-level OEM values join the model's OEM codes; fields holding several codes are split. — (D-14, D-41, Vehicles Q-16)
- Vehicle names are copied unchanged; structured fields are filled only where parsing is certain. — (D-34)
- Dictionary values migrate as they are, combined positions unsplit and coatings left among colours, except "Bez kategorii" (position); producer duplicates and unused producers go to the review list. — (D-45, D-46, D-47)

## 9. Cross-module interactions

- **Offers (consumer):** Offers lives on the catalogue; Catalogue does not depend on Offers. An offer stores only the id of its Glass model. Catalogue offers the following to Offers — (X-01; D-24):
  - **Glass model summary** for one or many ids: main Eurocode, all Eurocodes, OEM codes, position, colour (or none), equipment, fitted vehicles (all as names), withdrawn flag, thumbnail and pictograms. Used for offer lists, the offer view, approval and publishing. — (D-48, D-57)
  - **Candidate search:** a text matched against fragments of Eurocodes, OEM codes (RULE-04 comparison) and vehicle names and fields, with optional filters position, colour, make; results ordered Eurocode, OEM, vehicle, each with what it matched; withdrawn models only on request. A model matching by several routes appears once, under its best route. Minimum fragment length and result limits are set at delivery (V1: 3 characters, 20 results). It also returns the matching model ids, so Offers can filter its lists by position, colour, Eurocode, OEM and vehicle. — (D-49)
  - **Dictionary lists** of producers, positions and colours for picking and filtering; withdrawn values are not offered but their names stay readable. — (D-50)
  - **Events:** *Glass model changed* (Eurocodes, main Eurocode, OEM codes, position, colour, equipment or fitment, including when a vehicle or a position, colour or equipment value it uses changes); *Glass model withdrawn* / *restored*; *Producer changed* / *withdrawn*. Each carries the id; whoever reacts reads the summary. Creating a Glass model and deleting an unused entry announce nothing. — (D-51)
  - Offers in turn provides the "N offers" count and link on Glass model cards, and the knowledge that a Glass model or producer is in use (FR-24), without Catalogue depending on Offers; the mechanism is for the architecture. — (D-37, D-44)
- **Users:** roles Super Admin, Admin and User decide access (§3). — (D-42)
- **Audit:** what of catalogue changes is recorded is decided by the Audit module, not here (V1 logged only names and statuses). — (X-06)

## 10. External integrations

Catalogue talks to no external system. Integrations (WooCommerce now, others later) listen to the events in §9 to refresh adverts, keep their own mappings (e.g. position → shop category, producer → WordPress id) and may add a status section to a catalogue entry's view (FR-25). — (F-11, F-15, X-03; D-05, D-26, D-43)

## 11. Edge cases

- An admin types a Eurocode with spaces or lowercase → stored normalised; a search with spaces still finds it. — (RULE-03; D-41)
- An OEM code typed with or without its dash → treated as the same code. — (RULE-04)
- The same OEM code on three Glass models → a search by it returns all three, each marked "matched OEM …". — (D-14, D-49)
- Two vehicles would get the same proposed name → the admin must change one; names are unique. — (RULE-09)
- A colour is withdrawn while Glass models use it → those models keep and show it; it cannot be picked for another model. — (FR-24)
- A Glass model created twice for twin vehicles (e.g. `7814…` and `8613…`) → no merge: the admin re-points the duplicate's offers in Offers, deletes the now unused duplicate and adds its Eurocode to the right model. — (D-40)
- A position renamed → every Glass model with it, and their offers and adverts, show the new name; models are announced as changed. — (D-44, D-51)
- A position with no shape mapped, a colour with no fill, an equipment value with no pictogram → "unknown" shape, clear glass, universal pictogram. — (FR-18)
- A seller searches and the only match is withdrawn → no candidate; the seller leaves an identification hint. — (D-17, D-49)

## 12. Differences from V1

| V1 behaviour | V2 behaviour | Class | Reason | Trace |
|---|---|---|---|---|
| A eurocode is a bare code with OEM numbers and fitment; parameters live on offers | A Glass model holds its Eurocodes, OEM codes, parameters and fitment | correct | one source of glass data | D-01, D-07 |
| Offers carry ordered primary and alternative eurocodes | Alternatives become equal Eurocodes of one Glass model; the offer stores only the model | correct | 89% of pairs are the same glass | D-03, D-39 |
| Colour, type and equipment copied from a sibling offer | Read from the Glass model, never copied or overridden | correct | copying is the V1 defect | D-01, D-09 |
| Codes normalised on some paths only | Always normalised (RULE-03, RULE-04) | correct | lookups and uniqueness | D-41 |
| Vehicle is free text with a hand-typed name | Structured fields plus the kept, searchable name | correct | searching, consistency, no data loss | D-31, D-34 |
| Two vehicle links: eurocode fitment and `car_offer` | Fitment on the Glass model only | correct | one answer | D-11, D-15 |
| Dictionaries and vehicles with slug, Pending/Active approval and seller proposals | Name only (plus withdrawn flag for dictionaries); admin-edited; no slugs | correct | approval never used: all prod values active (G-09) | D-18, D-25, D-32, D-34 |
| Deleting a dictionary value leaves dangling ids | Delete only if unused, else withdraw | correct | no silent data loss | D-44 |
| Seller types free text matched after save | Seller picks from a search window | correct | no silent no-match | D-17, D-52 |
| Eurocode register is a table | Glass model cards with thumbnail and pictograms | new | readability, comparison | D-53, D-54–D-57 |
| Each dictionary knows its WordPress endpoint and key | Channel keys live in integrations | correct | separation | D-26 |
| `OfferExtras` table (dead) | Dropped | exclude | unused | D-25 |
| Submission action "Dodaj eurokod" | Model created during approval | exclude | no Submissions | D-20, D-38 |
| WordPress import of cars and dictionaries; backfill commands | Data migration only | exclude | panel only publishes | D-05, D-29 |

## 13. Approved additional functionality

- **ADD-01 Glass definition** and **ADD-03 Structured vehicles** were approved in the domain grilling and round 1 and are the core of this spec (FR-01–FR-11; D-01, D-04, D-07, D-31, D-34), not extras.
- **ADD-06 Glass model thumbnail** — D-54–D-57. Specified in FR-15–FR-18 (settings and drawing), FR-04 and FR-19 (where shown), RULE-12 and edge cases in §11. Shown on admin cards and in the picker; part of the summary for Offers; not on the offer view (the real piece's photos are there) and not in adverts.

## 14. Exclusions and out of scope

- Merging Glass models — fixed by hand; may come later as ADD-07 (D-40).
- "Can replace" relations between Glass models (D-12).
- Closed ARGIC-lettered equipment list (ADD-05) and VIN lookup (ADD-04) (D-58, D-16).
- A make → model → generation vehicle tree (D-31; later option per discovery ADD-03).
- Accessory model (ADD-A01): accessories stay Glass models with an accessory position until the client asks (D-27).
- A catalogue screen for sellers (D-42).
- Approval workflow and seller proposals for dictionary values (D-18, D-25).
- Shipping methods (D-33, pending).
- Anything about offers, adverts and channel synchronisation (D-24, D-26).

## 15. Pending decisions

- **D-33** Shipping methods — out of the model until the panel admin is asked (`zweryfikwoania_klientem.md` §1); if they return, more likely on the offer or seller than on the position. Blocks nothing in this spec.
- Client questions logged in `zweryfikwoania_klientem.md`: coatings among colours (§2, D-46, does not block); the pre-migration review list (D-13, D-45, D-47; blocks the import only).

## 16. Traceability

| Spec item | Discovery items | Reports | Decisions |
|---|---|---|---|
| FR-01, FR-02, WF-01, WF-03 | F-02, F-06, F-17, BR-03, BR-06, W-02, ADD-01 | r01, r04, r05 | D-01, D-04, D-07, D-09, D-35, D-37 |
| FR-03, WF-02, RULE-01–03 | F-19, BR-03, BR-05, Q-01, IMP-01 | r01, r05 | D-03, D-36, D-39, D-41 |
| FR-04, FR-05 | F-02, F-05, Q-15, IMP-05 | r01, r03 | D-30, D-37, D-49, D-53 |
| FR-06, FR-13, FR-24, WF-04, §7 | Q-10, IMP-03 | r02 | D-44 |
| FR-07–FR-09, RULE-09 | F-01, BR-01, BR-02, G-08, ADD-03 | r01 | D-31, D-34 |
| FR-10, FR-11, WF-05, RULE-10 | F-03, BR-04, BR-07, BR-17, Q-02, IMP-02 | r01, r04 | D-11, D-15, D-44 |
| FR-12, FR-14, RULE-08 | F-12, F-13, BR-10, BR-11, BR-13, Q-06, Q-11, Q-14 | r02 | D-18, D-25, D-32 |
| FR-15–FR-18, RULE-12, §13 | ADD-06 | — | D-54–D-57 |
| FR-19, FR-20, WF-06 | F-05, F-10, F-16, Q-08, IMP-04, W-01, BR-18 | r04 | D-17, D-19, D-38, D-52 |
| FR-21–FR-23, §9 | X-01, Q-05, F-20 | r03, r04 | D-24, D-48–D-51 |
| FR-25, §10 | F-11, F-15, IMP-06, Q-03, X-03 | r02, r03 | D-26, D-43 |
| RULE-04 | BR-03, Q-16 | r01, r03 | D-14, D-41 |
| RULE-05, RULE-06 | BR-12, Q-09 | r02, r04 | D-07, D-09, D-35 |
| RULE-07, §3 | G-01, G-06, BR-11 | r03 | D-18, D-42 |
| RULE-11 | Q-07, ADD-04 | r04, r05 | D-16 |
| §8 migration | BR-08, F-07, F-08, F-09, F-21, G-09, Q-16 | r01, r03, r06 (Offers) | D-13, D-34, D-45–D-47 |
| §12, §14 | F-04, F-07–F-11, F-13–F-18, F-20, F-21, ADD-02, ADD-05, ADD-07, Q-12 | r01–r05 | D-05, D-12, D-20, D-29, D-33, D-40, D-58 |
