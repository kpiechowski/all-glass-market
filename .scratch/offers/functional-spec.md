# Offers — functional spec

<!-- The authoritative functional reference for implementing Offers in V2.
     It describes behaviour, not implementation: no classes, tables, patterns or framework terms,
     except inside legacy traces. Data is described as information the business needs, not as schema.
     Every requirement carries a trace: (F-/BR-/W- ids, rNN; D- decision). -->

- **Agreed:** 2026-11-20 · **Discovery:** `.scratch/offers/discovery/discovery.md` (inputs also `.scratch/domain/glass-offer-model.md` and `.scratch/catalogue/functional-spec.md`) · **Audit:** r07, applied 2026-11-20 · **V1 baseline:** `511ce3b`
- **Decision counts** (decisions touching Offers, D-01–D-68; D-29 and D-56 count in two classes): preserve 6 · correct 46 · new 6 · exclude 6 · pending 1
- **Decision ids** are this module's (`discovery.md` §M). Catalogue's own decisions are cited as "Catalogue D-nn".

## 1. Purpose and responsibilities

Offers is where sellers and AllGlass staff put **physical pieces of glass up for sale**, and where the life of each piece is run: approval, reservation, inquiry, sale, withdrawal. Each Offer is the panel's single source of truth for its piece; channels only receive copies of it (ADR 0009, ADR 0010).

It owns:

- **Offers**: the piece (producer, year, condition, rating, descriptions, photos, private notes), its price, owner and status, and its reference to a Glass model or the seller's identification hint;
- **Approval** of sellers' Offers;
- **Reservations**, **Sales** and **Inquiries** of an Offer;
- the **Trash** of deleted Offers;
- the **VAT rate** setting used for buyers' prices;
- the offer lists, stat cards and the activity chart for sellers and admins.

It does not own: what a glass is (Glass models, Eurocodes, vehicles and the dictionaries, producer list included, belong to Catalogue), users and account types (Users), the change history (Audit), the Journal (Users), and adverts on channels (integrations). V1 kept the listing, the stock and the catalogue data in one record; its Submissions (review tickets) disappear (D-20).

## 2. Domain concepts

Defined in `GLOSSARY.md`: **Offer**, **Glass**, **Identification hint**, **Approval**, **Reservation**, **Sale**, **Inquiry** (section *Offers*); **Glass model**, **Eurocode**, **Main eurocode** (*Glass catalogue*); **Advert** (*Sales channels*); **Account type**, **Seller declaration**, **Journal**, **Journal entry**, **Deactivated User** (*Users and access*); **Change history**, **Actor** (*Audit*). Module-local terms (Polish labels come from the translation files at delivery):

- **Owner** (właściciel) — the User who created the Offer; never changes (D-40).
- **Offer number** — "AG" followed by 8 digits, assigned at creation and never changed; imported Offers keep their V1 number (D-29, BR-24).
- **Condition** (Stan) — Nowa, Używana or Outlet.
- **Rating** (Ocena) — 1 to 5 stars for a used or outlet piece; a new piece stores none and is shown with five stars (D-46).
- **Entered price** — the price the owner types. **Buyer's price** — what a buyer pays: the entered price for a Company owner, the entered price plus VAT for a Private owner (D-33).
- **Private notes** and **private photos** — the owner's own material about the piece, never published (D-49).
- **Trash** (Kosz) — where deleted Offers stay; not a status (D-30, D-43).
- **Outdated** (Nieaktualne) — the end state of an Inquiry closed because the Offer was reserved or sold another way (D-41).
- **Channel** (Kanał sprzedaży) — where a Sale happened: panel/direct, WooCommerce, OLX, other (D-34).

## 3. Actors and permissions

| Actor | May | May not | Trace |
|---|---|---|---|
| Seller (role User) | Create own Offers; edit own Offers in Draft, Pending, Rejected, Active, Withdrawn; send for approval; withdraw and resume own Offers; duplicate own Offers; change prices of own Offers singly and in bulk; reserve own Offers; release or sell a Reservation they or another seller-side made; mark own Offers sold; answer Inquiries on own Offers; see own Offers, their Sales, Reservations (with author) and Inquiries | See other sellers' Offers; approve; delete; return a Sale; touch a Reservation made by an admin; edit Reserved or Sold Offers; change the owner | D-17, D-31, D-32, D-34, D-40, D-42, BR-33 |
| Admin, Super Admin | Everything a seller may on any Offer; approve and reject; change the Glass model of any Offer, singly and in bulk; edit in every status without changing it; reserve, release and sell any Reservation; return a Sale; send Inquiries; delete, restore and permanently delete from the Trash; see the History tab | Create an Offer on behalf of a seller; change an owner | D-34, D-39, D-40, D-42, D-43, D-44 |
| Super Admin only | Change the VAT rate | — | D-54 |
| System | Withdraw a Deactivated User's Offers; close Inquiries as Outdated | — | D-41, D-50 |
| Catalogue | Provide Glass model summaries, the candidate search, the producer list and the picker; ask whether a Glass model or producer is in use | Change Offers | D-24, Catalogue §9 |
| Integrations | Listen to Offer events; read the offer summary for adverts | Change Offers; feed sales or reservations back | D-05, D-51 |

## 4. Functional requirements

### Creating and editing an Offer

- **FR-01** [Correct] A seller or an admin can create an Offer in one form, with no wizard: a "Szyba" section on top, then the piece (producer, year, condition, rating, description), photos, private notes and private photos, and price. The creator becomes the owner. — (F-31, BR-34; D-08, D-38, D-40)
- **FR-02** [Correct] In the "Szyba" section the user opens "Wybierz model szyby": a slide-over with the catalogue card list, its search and filters (provided by Catalogue, Catalogue FR-19); they mark one card and confirm, and the section shows that model's card. "Nie znam modelu" instead shows an identification hint field (any code, OEM number, vehicle or donor-car description, VIN allowed as text). The Offer has a Glass model or a hint, never glass parameters of its own. — (F-31, BR-10, BR-34, Q-18; D-01, D-09, D-10, D-16, D-17, D-38)
- **FR-03** [Correct] Producer is picked from Catalogue's producer list; a seller cannot add a producer. Year is optional for every condition. — (F-32, BR-34; D-18, D-25, D-45)
- **FR-04** [Correct] Rating 1–5 is entered for Używana and Outlet only. A Nowa Offer has no rating and is shown with five stars. — (BR-26; D-46)
- **FR-05** [Correct] Up to 10 public photos, the first is the main one, order changeable by dragging; up to 10 private photos; a public description for every condition; private notes. Private notes and photos are visible to the owner and admins only and never reach adverts. — (F-24, BR-34; D-49)
- **FR-06** [Correct] A Draft can be saved with any content. Sending for approval requires a Glass model or a hint, entered price ≥ 1, a condition, a rating for Używana or Outlet, and at least one public photo. — (BR-34; D-45)
- **FR-07** [Correct] A seller's change of the entered price, private notes or private photos takes effect at once and keeps the status. Any other change by a seller to an Active, Rejected or Withdrawn Offer sends it to Pending approval, and it leaves the channels until approved. — (BR-20, BR-35, Q-13, Q-17; D-31, D-49)
- **FR-08** [Correct] An admin can edit any Offer in any status, Sold included; the edit never changes the status. When an admin changes another owner's Offer, the owner gets a Journal entry. — (BR-20; D-42, D-44)

### Approval

- **FR-09** [Correct] An admin approves a Pending or Rejected Offer, which becomes Active; only an Offer with a Glass model can be approved. An admin rejects a Pending or Active Offer with a required reason; it becomes Rejected. The owner gets a Journal entry either way, with the reason on rejection. — (F-30, BR-36, W-11, Q-01; D-19, D-37, D-42)
- **FR-10** [New] Approving, the admin opens the catalogue slide-over prefilled from the hint, picks a model, or creates a new Glass model in place with the main Eurocode suggested from the hint (Catalogue FR-20); the new model is selected for the Offer. — (F-06, Q-02; D-19, D-44)
- **FR-11** [Correct] The approval queue is the Pending tab with its counter; there are no review tickets and no bell to all admins. Bulk approve approves the selected Pending and Rejected Offers that have a Glass model and reports how many were skipped (V1's bulk accept also skipped other statuses). — (F-01–F-07, Q-03, Q-04, BR-36; D-19, D-20, D-37)
- **FR-12** [Correct] On a Pending Offer the admin sees "Zmiany od ostatniego zatwierdzenia": each changed field with its old and new value, from the Change history; nothing for an Offer never approved. The admin's offer view has a History tab with the Offer's Change history. Sellers see no history. — (F-25, F-33, Q-16; D-20, D-39)
- **FR-13** [Preserve] An admin's own Offer is Active as soon as it is saved out of Draft with a Glass model; without one it goes to Pending. — (BR-20; D-40)

### Glass model on an Offer

- **FR-14** [New] An admin can change the Glass model of any Offer, singly or in bulk from the offer list ("Zmień model szyby"), without changing its status; the owner gets a Journal entry. — (D-44; Catalogue D-40)
- **FR-15** [Correct] Every screen and advert shows the Glass model's current data (Eurocodes, OEM codes, position, colour, equipment, vehicles); nothing is copied onto the Offer, and a catalogue change shows at once. — (BR-38, Q-20; D-01, D-09, D-24)

### Reservations

- **FR-16** [Correct] An admin or the owner can reserve an Active Offer with a free-text "for whom" note (no separate buyer data fields); the Offer becomes Reserved. An Offer has at most one Reservation at a time. Every Reservation shows who made it and when. Reservations never expire on their own. — (F-09, F-10, BR-13; D-02, D-21, D-34)
- **FR-17** [Correct] An admin can release or sell any Reservation. The owner can release or sell a Reservation unless an admin made it; an admin's Reservation is visible to the owner but locked. Releasing makes the Offer Active. — (F-11, BR-14, Q-08, Q-09; D-21, D-34)

### Sales

- **FR-18** [Correct] An admin or the owner marks an Active or Reserved Offer as sold (the owner not over an admin's Reservation), giving the channel and the sale price, prefilled with the buyer's price and editable. The Sale records the date, channel, entered price, VAT rate applied and buyer's price at that moment; the Offer becomes Sold. — (F-12, BR-13, Q-15; D-21, D-33, D-34)
- **FR-19** [Correct] Only an admin returns a Sale, with a required reason; the Sale stays in history marked returned, and the Offer becomes Active. — (F-13, BR-15, Q-08; D-21, D-34, D-42)
- **FR-20** [Correct] A seller sees the Sales of own Offers (date, channel, sale price, returned or not) on the Sold tab and on the Offer; admins see all. — (Q-15; D-52)

### Inquiries

- **FR-21** [Correct] An admin sends an Inquiry about an Active Offer to its owner, with an optional channel and a note; the Inquiry records the sending admin. The admin first sees how many Inquiries are already open on that Offer and from whom. Any number may be open. Inquiries are sent only on Active Offers, the one-piece reading of V1's rule that stock actions need available stock (BR-12). No buyer personal data is kept in separate fields. — (F-16, BR-12, BR-17, Q-07; D-22, D-35, D-41)
- **FR-21a** [Preserve] An admin sees the list of all Inquiries with their Offer, sender, state and dates. — (F-18; D-29)
- **FR-22** [Correct] The owner sees own Inquiries on "Zapytania"; opening one marks it read. The owner accepts it (only while the Offer is Active) or rejects it. Accepting creates the Reservation with the Inquiry's note, made in the name of the sending admin, linked to the Inquiry; being an admin's Reservation, it is locked for the seller (FR-17). The sending admin gets a Journal entry with the answer. — (F-17, F-18, BR-17; D-22, D-35, D-41, D-65)
- **FR-23** [Correct] Whenever an Offer stops being Active by any route (reserved, sold, withdrawn, deleted, rejected, sent back to Pending), its other open Inquiries close as Outdated and each sender gets a Journal entry. — (D-41, D-66)

### Withdrawal, deletion and the Trash

- **FR-24** [Correct] The owner or an admin withdraws a Pending, Rejected or Active Offer; it becomes Withdrawn and leaves the channels. "Wznów sprzedaż" returns a Withdrawn Offer to Active when it was approved before and has not changed since, apart from its entered price and private material; otherwise it must be edited and goes to Pending. An admin's own Offer is resumed to Active whenever it has a Glass model (FR-13). — (F-14, BR-16; D-30, D-42, D-67)
- **FR-25** [Correct] Only an admin deletes an Offer, from any status except Reserved. A deleted Offer goes to the Trash, keeps its status, leaves every list except the admin's Trash tab, and its data is kept for future statistics and still counts as a use of its Glass model and producer; it is left out of the "N ofert" count, the stat cards and the chart. Restoring returns it to the status it had. — (F-21, BR-22, BR-23, W-09; D-30, D-43)
- **FR-26** [Correct] From the Trash only, an admin can delete an Offer permanently: the Offer, its photos, Reservations, Sales and Inquiries are removed, nothing is left behind. — (F-21, BR-22; D-43)

### Duplicate

- **FR-27** [Correct] The owner or an admin duplicates an Offer: a new Draft owned by **whoever duplicates** (an admin duplicating a seller's Offer owns the copy), with the same Glass model or hint, description and entered price; photos, private material, producer, year, condition and rating are left empty. — (F-22, BR-25, BR-37; D-32)

### Price

- **FR-28** [Correct] The buyer's price is the entered price for a Company owner and the entered price plus VAT at the current rate for a Private owner, always by the owner's current Account type. A change of Account type or of the VAT rate changes the buyer's price of the affected Offers at once and is announced so adverts follow. — (F-23, F-27, BR-21, Q-15; D-33, D-54)
- **FR-29** [Preserve] Bulk price change on selected Offers: amount ± zł, percent ± %, or a fixed price ≥ 1. Offers whose price would be ≤ 0 are skipped, as are Reserved and Sold ones; the result message gives the counts. A seller acts only on own Offers; no re-approval. — (F-23, W-10, BR-35; D-31, D-53)
- **FR-30** [Correct] A Super Admin sets the VAT rate (initially 23%) in the Offers settings. — (BR-21; D-54)

### Lists and overview

- **FR-31** [Correct] Admin "Wszystkie oferty": tabs Pending (with counter), Active, Reserved, Sold, Withdrawn, Rejected, Drafts, Trash; filters by Eurocode, OEM or vehicle text, position and colour (through Catalogue's search), "without Glass model", seller, producer, condition, rating, price range, and Glass model (from the "N ofert" link); bulk approve, change price, change Glass model, withdraw, delete. — (F-29, F-26; D-47, D-48)
- **FR-32** [Correct] Seller section "Oferty": "Moje oferty" with the same tabs except Trash, and two bulk actions, change price and withdraw; "Rezerwacje" (Reservations on own Offers, with author); "Zapytania" (own Inquiries). Above the "Moje oferty" table, stat cards with an icon (they replace V1's "Moje oferty" and "Ostatnie oferty" dashboard widgets): own Active, Pending, Reserved, sold this month. — (F-28, F-15, F-26; D-06, D-47)
- **FR-33** [New] Catalogue's Glass model card shows "N ofert", the number of the model's Offers outside the Trash in every status, linking to "Wszystkie oferty" filtered by that model. — (D-48; Catalogue D-37)

### Journal

- **FR-34** [Correct] Offers writes a Journal entry (which also rings the bell) to the owner when someone else: approves, rejects (with reason), sends an Inquiry, makes or releases a Reservation, makes or returns a Sale, withdraws or deletes the Offer, or (admin) changes its data or Glass model; to the sending admin when their Inquiry is answered or becomes Outdated; to the owner when the System withdraws Offers of their deactivated account (a Deactivated User still receives entries, Users D-47). Nobody gets an entry for their own action. — (F-19, F-20, BR-18, Q-10; D-37, D-41, D-44, D-50)

### Deactivated sellers

- **FR-35** [New] When a seller is deactivated, their Active, Pending and Draft Offers become Withdrawn, by the System with the reason "seller deactivated"; Reserved Offers stay for an admin. Reactivation restores nothing. — (D-50)

## 5. Business rules and constraints

- **RULE-01** An Offer is exactly one physical piece; there is no quantity. Identical pieces are separate Offers. — (BR-11, BR-25, Q-06; D-02)
- **RULE-02** An Offer refers to at most one Glass model and never overrides its parameters; without a Glass model it has only an identification hint. — (D-01, D-09, D-10)
- **RULE-03** An Offer cannot become Active without a Glass model. — (D-19, D-40)
- **RULE-04** Every status change follows §7; no action sets a status outside its allowed transitions. — (BR-31, Q-01, Q-06, Q-08; D-42)
- **RULE-05** At most one Reservation is current on an Offer; an admin's Reservation can be released or sold only by an admin. — (D-34)
- **RULE-06** A Reserved Offer cannot be edited by its seller, withdrawn or deleted until the Reservation ends. — (D-42)
- **RULE-14** A bulk action (approve, change price, change Glass model, withdraw, delete) acts on each selected Offer under the same rules as the single action and skips those it may not change, reporting how many were done and skipped. — (BR-36; D-47, D-53)
- **RULE-07** The owner is the creator and never changes; duplicating gives the copy to whoever duplicates. — (D-32, D-40)
- **RULE-08** Buyer's price = entered price for a Company owner; entered price × (1 + VAT rate), rounded to grosze, for a Private owner. — (BR-21; D-33, D-54)
- **RULE-09** A Sale's recorded prices and VAT rate never change after it is made. — (Q-15; D-21, D-33)
- **RULE-10** Every Reservation, Sale, return and status change is one indivisible step and is recorded in the Change history. — (IMP-04, W-06; D-56)
- **RULE-11** A seller sees only their own Offers, never other sellers'; they see the catalogue through the picker. — (BR-33; D-17)
- **RULE-12** Offers is the source of truth; nothing from a channel changes an Offer. — (BR-19; D-05)
- **RULE-13** The offer number is unique and never changes. — (BR-24; D-29)

## 6. Workflows

### WF-01 Create and send for approval

- **Actors / trigger:** seller or admin, "Utwórz".
- **Preconditions:** authenticated with offer creation rights.
- **Main flow:** 1. Fill the form (FR-01–FR-05). 2. Either save as Draft, or send. 3. Sending checks FR-06. 4. Seller: the Offer is Pending. Admin: Active if it has a Glass model, else Pending (FR-13). 5. The offer number is assigned at creation.
- **Postconditions:** the Offer exists with its owner; a Pending Offer appears in the admin's Pending tab.
- **Errors:** a required field missing on send → refused, naming the fields; the Draft is still savable.
- **Trace:** (W-01, F-31, F-34; D-38, D-40, D-45)

### WF-02 Seller edits an Offer

- **Actors / trigger:** seller on own Offer in Draft, Pending, Rejected, Active or Withdrawn.
- **Main flow:** 1. Edit and save. 2. Only entered price, private notes or private photos changed → saved, status kept; Active adverts get the new price. 3. Anything else changed on an Active, Rejected or Withdrawn Offer → Pending, and the Offer leaves the channels.
- **Errors:** Reserved or Sold → no edit offered and refused if attempted.
- **Trace:** (W-02, BR-20, Q-13; D-31, D-42, D-49)

### WF-03 Approve or reject

- **Actors / trigger:** admin, on the Pending tab, the offer view or in bulk.
- **Main flow:** 1. The admin reads the Offer and "Zmiany od ostatniego zatwierdzenia". 2. Without a Glass model: opens the slide-over prefilled from the hint, picks or creates a model (FR-10). 3. Approve → Active, the Offer becomes a candidate for adverts; or reject with reason → Rejected. 4. Journal entry to the owner.
- **Alternative paths:** bulk approve skips Offers without a Glass model and reports them.
- **Errors:** approve without a Glass model → refused; reject without reason → refused.
- **Trace:** (W-03, W-11, F-30; D-19, D-20, D-39, D-44)

### WF-04 Reserve, release, sell, return

- **Actors / trigger:** admin or owner on the Offer; an accepted Inquiry.
- **Main flow:** 1. Reserve an Active Offer with a note → Reserved, open Inquiries Outdated. 2. Release (rules FR-17) → Active. 3. Sell from Active or Reserved, with channel and price → Sold; a current Reservation ends as sold; open Inquiries Outdated. 4. Return (admin, reason) → Active, the Sale marked returned.
- **Errors:** the owner tries to release or sell an admin's Reservation → refused; reserve a non-Active Offer → refused.
- **Trace:** (W-05, W-06, F-09–F-13; D-21, D-34, D-41)

### WF-05 Inquiry

- **Actors / trigger:** admin on an Active Offer.
- **Main flow:** 1. The admin sees the open Inquiries count, writes the note, picks the channel, sends. 2. The owner gets a Journal entry. 3. The owner opens it (read), then accepts → Reservation in the admin's name with the note; or rejects. 4. The sending admin gets a Journal entry.
- **Errors:** accepting while the Offer is no longer Active → refused, the Inquiry is shown as Outdated.
- **Trace:** (W-07, F-16, F-17; D-22, D-35, D-41)

### WF-06 Withdraw and resume

- **Main flow:** 1. Owner or admin withdraws → Withdrawn, adverts end. 2. "Wznów sprzedaż" → Active if approved before and unchanged since; else editing sends it to Pending.
- **Trace:** (W-08, F-14; D-30, D-42)

### WF-07 Delete, restore, delete permanently

- **Actors / trigger:** admin.
- **Main flow:** 1. Delete (not Reserved) → Trash, status kept, adverts end, owner's Journal entry. 2. Restore from the Trash → back with its status (adverts follow if Active or Reserved). 3. Delete permanently from the Trash → removed with everything that belongs to it.
- **Trace:** (W-09, F-21; D-43)

### WF-08 Duplicate

- **Main flow:** 1. Owner or admin chooses "Duplikuj". 2. A new Draft owned by the actor with the copied fields (FR-27). 3. The actor completes it and sends it (WF-01).
- **Trace:** (F-22; D-32)

### WF-09 Seller deactivated

- **Trigger:** Users announces a deactivation.
- **Main flow:** Active, Pending and Draft Offers of that seller → Withdrawn (System); Reserved stay; open Inquiries on withdrawn Offers become Outdated.
- **Trace:** (D-50, D-41)

## 7. States and transitions

Statuses: **Draft** (Szkic), **Pending approval** (Oczekuje na zatwierdzenie), **Rejected** (Odrzucona), **Active** (Aktywna), **Reserved** (Zarezerwowana), **Sold** (Sprzedana), **Withdrawn** (Wycofana). Being in the Trash is separate and keeps the status.

| From | To | Who | Guard | Effect |
|---|---|---|---|---|
| Draft | Pending | seller owner | FR-06 | enters the queue |
| Draft | Active | admin owner | FR-06 and a Glass model (else Pending) | advert candidate |
| Pending | Active | admin | Glass model | owner's Journal; advert candidate |
| Pending | Rejected | admin | reason | owner's Journal |
| Rejected | Pending | seller owner | after editing, FR-06 | enters the queue |
| Rejected | Active | admin | Glass model | owner's Journal |
| Active | Rejected | admin | reason | adverts end; owner's Journal |
| Active | Pending | seller owner | a change other than price/private material (FR-07) | adverts end |
| Active | Reserved | admin, owner, accepted Inquiry | no current Reservation | note and author recorded; Inquiries Outdated |
| Active, Reserved | Sold | admin; owner (not over an admin's Reservation) | channel, price | Sale recorded; adverts end; Inquiries Outdated |
| Reserved | Active | admin; owner (not an admin's Reservation) | — | Reservation ends |
| Sold | Active | admin | reason | Sale marked returned |
| Pending, Rejected, Active | Withdrawn | owner, admin, System (D-50) | — | adverts end |
| Draft | Withdrawn | System | seller deactivated (D-50) | — |
| Withdrawn | Active | owner, admin | approved before, unchanged since | advert candidate |
| Withdrawn | Pending | seller owner | after editing | enters the queue |
| any except Reserved | (Trash) | admin | — | leaves lists; adverts end |
| (Trash) | previous status | admin | — | back in lists |
| (Trash) | (gone) | admin | — | removed with its photos, Reservations, Sales, Inquiries |

Inquiry states: **Sent** → **Read** (owner opens) → **Accepted** or **Rejected**; Sent or Read → **Outdated** (FR-23, WF-09). Accepted, Rejected and Outdated are final. — (BR-17; D-41)

## 8. Data requirements

- **Offer:** offer number; owner; status; in Trash or not, since when (with the status it keeps); Glass model or identification hint; producer; year; condition; rating (not for Nowa); public description; up to 10 ordered public photos; up to 10 private photos; private notes; entered price; when it was created, last changed, first approved (the chart's "added") and last approved (the "changes since" block and resuming).
- **Reservation:** Offer; author; note; when made; how it ended (released, sold) and when; the Inquiry it came from, if any.
- **Sale:** Offer; date; channel; entered price, VAT rate and buyer's price at sale; who marked it; returned or not, with reason, who and when.
- **Inquiry:** Offer; sending admin; channel (optional); note; state; when sent, read and answered.
- **Setting:** VAT rate.
- **Derived, not stored:** buyer's price of an Offer (RULE-08); counts on tabs, stat cards and the chart.
- **Not kept:** quantity; per-offer eurocode strings, alternative eurocodes, OEM text, VIN field; glass type, colour and equipment on the Offer; expiry date; Submissions; versions (the Change history replaces them); the per-offer journal of V1 (the Users Journal replaces it); shipping method (pending).

**Migration constraints** (detail in `.scratch/data-migration/offers/spec.md`, Phase 9):

- The V1 offers with quantity > 1 (16, no sale or reservation) are checked by the client first; the quantity standing at import is split into one Offer per piece. — (D-02, D-68)
- V1 statuses map onto §7; V1 "Usunięta" offers go to the Trash. — (D-30, D-43)
- Each Offer's Glass model is the one its V1 eurocodes joined into (Catalogue migration); V1 "without eurocode" text and the typed eurocode become the hint when no Glass model results. — (D-03, D-10, D-13)
- Submissions are not migrated; what the admin needs is already on the Offer and in its history. — (D-20)
- WooCommerce-imported sales and reservations become plain Sales and Reservations. — (D-05, BR-19)
- Ratings of Nowa offers are dropped. — (D-46)

## 9. Cross-module interactions

- **Catalogue (provider):** Offers stores only the Glass model's id. It uses Catalogue's Glass model summary for lists, the offer view, approval and adverts; the candidate search (and the matching model ids) for its filters; the producer list; the picker slide-over (Catalogue FR-19, D-67) and in-place model creation (Catalogue FR-20). It listens to *Glass model changed / withdrawn / restored* and *Producer changed / withdrawn* to refresh what it announces to channels. — (X-01; D-24; Catalogue §9)
- **Catalogue (consumer of Offers' knowledge):** Offers answers whether a Glass model or producer is in use by any Offer it keeps, Trash included, and gives the "N ofert" count and link (FR-33), without Catalogue depending on Offers; the mechanism is for the architecture. — (D-43, D-48; Catalogue FR-24, D-37)
- **Users:** the owner's Account type decides the buyer's price, and its change is listened to (FR-28); a seller's deactivation is listened to (FR-35); Journal entries are written through Users (FR-34); roles decide access (§3). — (X-06; D-33, D-37, D-50)
- **Audit:** Offers registers Offers as auditable; the History tab and "Zmiany od ostatniego zatwierdzenia" read the Change history. — (X-07; D-39)

## 10. External integrations

Offers talks to no external system. Integrations (WooCommerce now, OLX later) react to Offers' announcements — became a candidate for adverts (approved, resumed, returned, released, restored), changed while a candidate (including a buyer's price change from Account type or VAT rate, and a Glass model change), reserved, sold, and stopped being a candidate (rejected, sent back to Pending, withdrawn, deleted) — and read the **offer summary for adverts**: offer number, Glass model summary, producer, year, condition, rating (five stars shown for Nowa if the channel wants), public description, public photos in order, buyer's price, and a reservation flag. An Offer is a candidate for adverts while Active or Reserved. Which Offers go to which channel and how failures are shown are decided in the WooCommerce grilling; a failing channel never blocks or undoes an action in Offers. — (X-02, BR-28, BR-29, IMP-06; D-05, D-26, D-51)

## 11. Edge cases

- Two admins send Inquiries on one Offer; the owner accepts the second → it becomes the Reservation, the first is Outdated and its sender is told. — (D-41)
- An admin reserved an Offer by phone; the owner opens it → sees "Zarezerwowana przez <admin>, <date>", no release or sell buttons. — (D-34)
- A seller changes only the price of an Active Offer → stays Active, adverts show the new price; changes the price and a photo → Pending. — (D-31)
- A seller edits private notes on an Active Offer → stays Active. — (D-49)
- A Private seller becomes Company → buyer's prices of their Offers drop by the VAT at once; recorded Sales unchanged. — (D-33)
- The VAT rate changes → every Private seller's Offer gets a new buyer's price; Sales keep their rate. — (D-54)
- The Glass model of an Offer is withdrawn in Catalogue → the Offer keeps it and shows it; the model cannot be picked for new Offers. — (Catalogue FR-24)
- Two duplicate Glass models for twin vehicles → the admin bulk-changes the Offers to the right model, then deletes the empty duplicate in Catalogue. — (D-44; Catalogue D-40)
- An admin duplicates a seller's Offer → the copy belongs to the admin. — (D-32)
- An Offer in the Trash still holds its Glass model → Catalogue refuses to delete that model; it may be withdrawn. — (D-43)
- A test Offer created by mistake → admin deletes it, then deletes it permanently from the Trash. — (D-43)
- A Sale is returned → Offer Active again; the chart's "Sprzedane" no longer counts it. — (D-21, D-58)
- A seller is deactivated with one Reserved Offer → that one stays Reserved; the rest are Withdrawn. — (D-50)
- Bulk price −50% on offers of 1 zł → skipped, counted in the message. — (D-53)
- Bulk approve over five Offers, two without a Glass model → three approved, two reported. — (D-19)

## 12. Differences from V1

| V1 behaviour | V2 behaviour | Class | Reason | Trace |
|---|---|---|---|---|
| Offer holds quantity; stock derived from sales and reservations | One piece per Offer; one Reservation at a time | correct | no partial sale ever happened | D-02 |
| Offer holds eurocode text, alternative eurocodes, OEM, VIN, type, colour, equipment | Offer refers to a Glass model, or has a hint | correct | one source of glass data | D-01, D-03, D-10, D-16 |
| Free-text code matched after save, attributes copied from sibling offers | Picker slide-over; no matching after save | correct | no silent no-match | D-17, D-38 |
| Review tickets (Zgłoszenia) for every seller save | Pending tab; changes from the Change history | exclude | queue unused (1182/1213 unread) | D-20, D-39 |
| Every seller save re-approved; bulk price not | Price never; all else always | correct | one rule | D-31 |
| Any action sets any status | Guarded transitions (§7) | correct | forced statuses | D-42 |
| Deletion is a status; permanent delete orphans sales | Trash apart from status; permanent delete only from Trash, removes everything | correct | statistics, no orphans | D-30, D-43 |
| Restore always to Oczekuje | Restore to previous status | correct | no needless re-approval | D-43 |
| Only a reservation's author may release or sell it | Admin's Reservation locked for the seller; admin may handle all | correct | V1 rule kept, private-author defect removed | D-34 |
| Sale stores quantity only; value uses today's price | Sale stores prices and VAT rate at sale, and the channel | correct | sales history | D-21, D-33 |
| VAT 23% in code | Super Admin setting | correct | maintainability | D-54 |
| Inquiry without sender; answers to all admins | Sender recorded; answer to the sender; others Outdated on reservation | correct | accountability, no double booking | D-35, D-41 |
| Journal and bell as two channels | One Journal entry, bell automatic; only others' actions | correct | noise | D-37 |
| Nowa forced to rating 5 | No rating, shown as five stars | correct | honest data, same look | D-46 |
| Duplicate → Oczekuje; copy of admin kept the old owner (bug) | Duplicate → Draft owned by the duplicator | correct | bug, incomplete copies | D-32 |
| Wygasła status, expiry command removed | No expiry | exclude | unused | D-36 |
| Three histories (versions, changes log, activity log) | The Change history | correct | one history | D-29, D-39 |
| WooCommerce import creates sales and reservations | Nothing pulled from channels | exclude | panel only publishes | D-05 |
| Otomoto adverts | Dropped | exclude | channel gone | D-56 |
| Seller may add a producer (pending) | Admin-only list in Catalogue | exclude | catalogue is admin's | D-18 |
| Admin could pick status freely on create | Admin's Offer is Active only with a Glass model | correct | RULE-03 | D-40 |
| Required: price and condition | Plus Glass model or hint, rating for used, one public photo | correct | publishable quality | D-45 |
| — | Deactivating a seller withdraws their Offers | new | no ghost listings | D-50 |
| — | Bulk change of Glass model | new | fixing duplicate models | D-44 |

## 13. Approved additional functionality

- Accessories (seal, moulding, frame) stay ordinary Offers of a Glass model with an accessory position; a separate Accessory model is not built (D-27, preserve).
- **ADD-01 Seller picks a Glass model** — approved in the domain grilling (D-17); it is FR-02 and WF-01, not an extra.
- **ADD-03 Inquiries record who asks** — FR-21, FR-22 (D-35).
- **ADD-05 Activity chart** (D-58):
  - **FR-36** [New] One line chart over the last 12 months with two lines, "Dodane do sprzedaży" (Offers approved that month) and "Sprzedane" (Sales made that month, returned ones excluded); no money amounts. Offers in the Trash do not count.
  - **FR-37** [New] A seller sees it for own Offers on "Moje oferty" under the stat cards, collapsible; an admin sees it for all Offers above "Wszystkie oferty", filterable by seller.
  - **Edge cases:** little or no history → zeros, still shown; migrated V1 Offers count as added on their creation date, so the import months (2025-09–2026-01) show a spike, accepted.
  - The dashboard redesign is out of scope.

## 14. Exclusions and out of scope

- Review tickets / Submissions — D-20.
- Quantity and derived stock — D-02.
- Automatic expiry — D-36 (client §7).
- Seller-added producers or any catalogue change by sellers — D-18.
- Creating an Offer on behalf of a seller; changing the owner — D-40.
- Pulling sales, reservations or offers from channels; Otomoto — D-05, D-56.
- Channel selection, advert payloads, publish failure handling — WooCommerce grilling (D-51).
- A separate seller stock of unlisted pieces — D-06.
- Sales reports and admin statistics pages; dashboard redesign — D-52, D-58.
- A separate admin revision screen — ADD-04, pending (client §8).
- Shipping — D-55, pending.

## 15. Pending decisions

- **D-55** Shipping methods — out until the panel admin is asked (`zweryfikwoania_klientem.md` §1). Blocks nothing.
- **ADD-04** Separate admin revision screen — client §8. Blocks nothing (FR-12 covers the need).
- To confirm with the client, already decided for delivery: re-approval scope (D-31, §6), no expiry (D-36, §7).

## 16. Traceability

| Spec item | Discovery items | Reports | Decisions |
|---|---|---|---|
| FR-01–FR-06, WF-01 | F-31, F-32, F-34, F-24, BR-34, BR-26, Q-18, W-01 | r01, r02 | D-08, D-10, D-17, D-38, D-40, D-45, D-46, D-49 |
| FR-07, FR-08, WF-02 | BR-20, BR-35, Q-13, Q-17, IMP-05, W-02 | r01, r02 | D-31, D-42, D-44, D-49 |
| FR-09–FR-13, WF-03 | F-01–F-07, F-30, BR-36, W-03, W-11, Q-01–Q-04, Q-16 | r01, r02, r04 | D-19, D-20, D-37, D-39, D-40, D-44 |
| FR-14, FR-15 | BR-38, Q-20, X-01 | r06 | D-01, D-09, D-24, D-44 |
| FR-16–FR-20, WF-04 | F-08–F-13, F-15, BR-11–BR-15, Q-06, Q-08, Q-09, Q-15, W-05, W-06 | r03 | D-02, D-21, D-33, D-34, D-52 |
| FR-21–FR-23, WF-05 | F-16–F-18, BR-17, Q-07, ADD-03, W-07 | r03 | D-22, D-35, D-41 |
| FR-24–FR-26, WF-06, WF-07 | F-14, F-21, BR-16, BR-22, BR-23, W-08, W-09 | r01, r03, r05 | D-30, D-42, D-43 |
| FR-27, WF-08 | F-22, BR-25, BR-37 | r01, r02 | D-32 |
| FR-28–FR-30 | F-23, F-27, BR-21, BR-35, Q-15, W-10 | r01, r05 | D-33, D-53, D-54 |
| FR-31–FR-33 | F-15, F-26, F-28, F-29 | r01, r02 | D-47, D-48 |
| FR-34 | F-19, F-20, BR-18, Q-10, IMP-07 | r03 | D-37, D-41, D-44, D-50 |
| FR-35, WF-09 | X-06 | — | D-50 |
| FR-36, FR-37 | ADD-05, BR-39 | r06 | D-58 |
| §7, RULE-04 | BR-31, Q-01, Q-06, Q-08, Q-14, IMP-02 | r01 | D-30, D-42 |
| RULE-08, RULE-09 | BR-21, Q-15, IMP-03 | r01, r05 | D-21, D-33, D-54 |
| RULE-10 | IMP-04, W-06 | r03 | D-56 |
| §8 migration | BR-19, BR-24, BR-25, F-01–F-07 | r03, r04, r06 | D-02, D-05, D-13, D-20, D-30, D-46 |
| §10 | X-02, X-03, X-05, BR-28, BR-29, IMP-06 | r01, r05 | D-05, D-26, D-51, D-56 |
| §12, §14 | Q-11, Q-12, BR-30, ADD-02, ADD-04, F-32 | r01–r05 | D-06, D-18, D-27, D-36, D-55, D-56 |
