# r07 — spec-audit: Offers functional spec

Question: Is `.scratch/offers/functional-spec.md` a complete, traceable, implementation-free functional spec of the agreed decisions? · V1 commit: 511ce3b

Paths below: `S` = `.scratch/offers/functional-spec.md`, `D` = `.scratch/offers/discovery/discovery.md`, `C` = `.scratch/catalogue/functional-spec.md`.

## Summary
The spec is largely faithful and well traced. Every FR, RULE and WF carries a trace. The decision counts in the header (6/37/6/5/1) match section M exactly. The Catalogue contract (§9, FR-19, FR-20, FR-24) is mirrored correctly. Per check:
1. Traceability: no untraced requirement. Four requirements add a rule the cited decision does not state (T1–T4).
2. Decisions: no decision is contradicted. Three are misreflected or missing: D-50 (Draft → Withdrawn absent from §7), D-27 (listed as an exclusion although it is "preserve"), D-35/D-34 (reservation author), plus omissions (R1–R8).
3. IMP/ADD: every IMP-/ADD- item in the spec has a decision. Only the "failing channel never blocks" sentence rests on an implication (A1).
4. Implementation leaks: none serious. Three borderline phrases (I1–I3).
5. Reader gaps: 14 findings (G1–G14). The ones that matter: inquiries on an Offer that leaves Active are undefined; "Wznów" with admin-owned or price-changed Offers; Trash "still counts in statistics" against the chart that excludes Trash; the chart needs approval events while §8 keeps only "last approved"; the migrated Trash status; the locked reservation that an owner's accepted Inquiry creates.

## Findings

### Check 1 — Requirements with no trace to F-/BR-/W- or D-
Result: none without a trace. Traces that do not support the claim:

- T1 FR-22 "made in the name of the sending admin"
  - Tag: inferred · Impact: high (permissions)
  - What happens: D-35 says accepting "creates the Reservation with that note, linked to the Inquiry". It does not say who is the author. The spec makes the admin the author. By FR-17 / RULE-05 / D-34 the Reservation is then locked for the owner who accepted it. The owner then cannot release or sell a Reservation they created by accepting.
  - Evidence: S:92 FR-22; D:496 D-35 (no author); D:495 D-34 (admin's Reservation locked); S:81 FR-17.
  - Needs a decision or a client confirmation.
- T2 FR-21 "an Inquiry about an **Active** Offer" (sending restricted to Active)
  - Tag: inferred · Impact: normal
  - What happens: D-41 restricts only accepting to Active. No decision restricts sending. The F-16/BR-17 trace shows no such rule.
  - Evidence: S:91; D:505 D-41.
- T3 FR-34 / §3 "to the owner when the System withdraws Offers of their deactivated account"
  - Tag: inferred · Impact: normal
  - What happens: D-50 has no Journal clause. D-37 does not list it. The recipient is a Deactivated User who cannot sign in.
  - Evidence: S:119, S:48; D:517 D-50; D:498 D-37.
- T4 FR-11 / WF-03 / §11 bulk approve "reports how many were skipped" (WF-03: "reports them")
  - Tag: inferred · Impact: normal
  - What happens: BR-36 says only that the rest are skipped. D-19 says only that approval needs a Glass model. The skip-and-report message is the spec's own. FR-11 says "how many", WF-03 says the Offers are reported. The two differ.
  - Evidence: S:69, S:163, S:276; D:157 BR-36.
- T5 "who marked it" on a Sale (§8) and the quoted labels "Duplikuj" (WF-08), "Utwórz" (WF-01) and "Zarezerwowana przez <admin>, <date>" (§11)
  - Tag: unknown · Impact: normal
  - What happens: no decision or inventory item quotes these. The brief says Polish labels are quoted verbatim, so a made-up string must not appear as if it were quoted. Only "duplikuj" appears, in F-16/F-22 (lower case, no quotes).
  - Evidence: S:147, S:171, S:194, S:264, S:234.

### Check 2 — Decisions not reflected, or reflected wrongly
Settled and faithful (spot list): D-01, 02, 03, 05, 07–10, 13, 17–26, 29–34 (except below), 36–49, 51–54, 55, 58. The header class counts verify against section M (preserve 6 / correct 37 / new 6 / exclude 5 / pending 1; D-28 and D-57 are n/a).

- R1 D-50 Draft → Withdrawn missing from §7
  - Tag: defect? · Impact: high (state)
  - What happens: D-50 and FR-35/WF-09 withdraw Draft Offers. The §7 row "Pending, Rejected, Active → Withdrawn" does not include Draft, and D-42's list has no Draft → Withdrawn. RULE-04 says "no action sets a status outside its allowed transitions". A Draft is also not withdrawable by hand (FR-24), so the System transition is the only one, and the table omits it.
  - Evidence: S:221, S:123, S:130, S:124; D:517, D:506.
- R2 D-27 accessory Offers listed under "Exclusions"
  - Tag: defect? · Impact: normal
  - What happens: D-27 is class preserve: an accessory is an ordinary Offer of a Glass model with an accessory position. The spec lists "Accessory model — D-27" among exclusions and does not say that the 70 V1 accessory Offers migrate as ordinary Offers. The reader cannot tell whether accessories are in or out of Offers.
  - Evidence: S:322 (§14); D:485 D-27.
- R3 D-35 "no buyer personal data in fields"
  - Tag: confirmed (omission) · Impact: normal
  - What happens: the constraint is absent from FR-21 and §8. A reader could add a buyer name field.
  - Evidence: D:496; S:91, S:235.
- R4 D-34 "Reservations do not expire"
  - Tag: confirmed (omission) · Impact: normal
  - What happens: the spec states automatic expiry only for Offers (D-36). It does not state the same for Reservations.
  - Evidence: D:495; S:320, S:321.
- R5 D-29 / F-26: widgets and user-view stats not placed
  - Tag: confirmed (omission) · Impact: normal
  - What happens: D-29 keeps F-26 "tabs with counts and widgets". F-26 also covers the dashboard widgets "Moje oferty" and "Ostatnie oferty" and the offer stats on the user view. The spec has the tabs and the seller stat cards (FR-32). The admin stat cards, the latest-offers widget and the user-view stats are neither specified nor excluded. D-58 puts only the dashboard redesign out of scope.
  - Evidence: D:77, D:490; S:114, S:328.
- R6 D-35 / F-18 admin Inquiry list and delete
  - Tag: confirmed (omission) · Impact: normal
  - What happens: D-29 says F-08–F-20 keep their purpose. F-18 is the admin list, details and delete of Inquiries. The spec gives the owner a list and the admin only a count at sending. No admin view of sent Inquiries and their state, and no word on deleting an Inquiry (permanent delete of an Offer removes them, FR-26).
  - Evidence: D:69, D:490; S:91–93.
- R7 Class tags differ from the decision class
  - Tag: confirmed · Impact: normal
  - What happens: FR-23 is [New] but D-41 is "correct". FR-13 and the §12 row "Admin could pick status freely" are "correct" but D-40 is "preserve". The §1 counts are by decision, so they are unaffected. The per-item tags mislead.
  - Evidence: S:93, S:71, S:302; D:505, D:501.
- R8 D-15 "`car_offer` is dropped; vehicles reach an Offer only through the Glass model"
  - Tag: confirmed (partial) · Impact: normal
  - What happens: FR-15 shows vehicles via the model. §8 "Not kept" and §12 do not list the V1 link between vehicles and Offers. Weak, not wrong.
  - Evidence: D:473; S:76, S:238.

### Check 3 — IMP-/ADD- items without an approving decision
Result: none missing. Every IMP-/ADD- item cited in the spec has a decision: IMP-01 → D-20; IMP-02 → D-30/D-42; IMP-03 → D-21/D-33; IMP-04 → D-21/D-56; IMP-05 → D-31; IMP-06 → D-05/D-24/D-26/D-51; IMP-07 → D-37; ADD-01 → D-17; ADD-03 → D-35; ADD-05 → D-58; ADD-02 and ADD-04 are shown as excluded and pending.

- A1 §10 "a failing channel never blocks or undoes an action in Offers"
  - Tag: inferred · Impact: normal
  - What happens: IMP-06 carries "partly D-05, D-24, D-26" and defers "retries and failure display" to the WooCommerce grilling. The "never blocks" clause follows from "integrations react to events" but no decision states it.
  - Evidence: S:258; D:394, D:518.
- A2 Stale status text in the discovery log
  - Tag: confirmed · Impact: normal
  - What happens: section I still says IMP-02 "decision: open" and IMP-03 "VAT open". D-42, D-33 and D-54 settle both. This is a discovery bookkeeping issue, not a spec fault.
  - Evidence: D:390, D:391.

### Check 4 — Implementation leaking
Result: no class, table, framework or pattern names as requirements. The "slide-over" is a decided UI element (D-38). Borderline:

- I1 §9 "Offers stores only the Glass model's id"
  - Tag: confirmed · Impact: normal
  - What happens: a data-design statement. The business wording would be "an Offer refers to one Glass model". Also repeated in §8 "Derived, not stored" and §9 "Setting".
  - Evidence: S:251, S:237.
- I2 §9 "Offers registers Offers as auditable"
  - Tag: confirmed · Impact: normal
  - What happens: describes a mechanism with the Audit module. Business wording: "every change to an Offer is in the Change history".
  - Evidence: S:254.
- I3 §3/§9/§10 "Listen to Offer events", "announcements"
  - Tag: confirmed · Impact: normal
  - What happens: event-style coupling is the architecture of ADR 0010, stated as a requirement. It is tolerable as a boundary description. §9 already says "the mechanism is for the architecture" for one case. §2 "Polish labels come from the translation files" is a delivery note and is also borderline.
  - Evidence: S:50, S:253, S:258, S:29.

### Check 5 — Where a reader who never saw V1 could not act
- G1 Open Inquiries when the Offer leaves Active by any route other than reserve or sell
  - Tag: ambiguous · Impact: high (state)
  - What happens: FR-23 closes Inquiries only on reserve or sale. WF-09 and §3 (System) close them on withdrawal by deactivation, citing D-41/D-50, but neither decides that. A manual withdrawal, a rejection, a move to Pending, or deletion of an Offer with open Inquiries is undefined. WF-05 says accepting on a non-Active Offer is "refused, the Inquiry is shown as Outdated", which implies a closure not stated in the §7 Inquiry states (only "FR-23, WF-09"). Whether senders then get a Journal entry is undefined for these routes.
  - Evidence: S:93, S:200, S:48, S:178, S:228; D:505, D:517.
- G2 "Wznów sprzedaż: approved before and unchanged since"
  - Tag: ambiguous · Impact: high (state)
  - What happens: three gaps. (a) "Unchanged" is not defined: does a price change, which keeps the status (FR-07), count? Does an admin edit? (b) A Withdrawn Offer that is not resumable "must be edited and goes to Pending" (FR-24). That holds for a seller (FR-07) but not for an admin-owned Offer, because FR-08 says an admin's edit never changes the status, and the admin cannot approve a Withdrawn Offer (FR-09: Pending or Rejected). An admin-owned or admin-edited Withdrawn Offer that was never approved has no path to Active. (c) An Offer withdrawn from Draft by System was never approved.
  - Evidence: S:97, S:62, S:63, S:67, S:222–223.
- G3 Trash "still counts in statistics" against the chart and counts that exclude Trash
  - Tag: defect? · Impact: normal
  - What happens: FR-25 says a deleted Offer "still counts in statistics". FR-36 says Offers in the Trash do not count. FR-33 excludes it from "N ofert". The two decisions (D-43 and D-58/D-48) point opposite ways, and "statistics" is undefined, because admin statistics pages are out of scope (§14). The stat cards (FR-32) do not say either.
  - Evidence: S:98, S:312, S:115, S:328; D:507, D:512, D:528.
- G4 Chart needs every approval event; §8 keeps one timestamp
  - Tag: defect? · Impact: normal
  - What happens: "Dodane do sprzedaży" counts "Offers approved that month". §8 stores only "last approved". Not defined: whether an Offer approved twice (reject then approve, edit then re-approve, "Wznów") counts once or twice; whether a Draft → Active admin Offer (FR-13) and a Withdrawn → Active resume count as approved. With "last approved" alone an old Offer re-approved would move to the new month.
  - Evidence: S:312, S:232, S:97, S:71.
- G5 Migrated Offers in the Trash have no known previous status
  - Tag: unknown · Impact: high (data)
  - What happens: V1 deletion set "Usunięta" and lost the previous status (BR-22). §8 says V1 "Usunięta" Offers go to the Trash, and FR-25 says restore returns to "the status it had". Nothing says which status a migrated Trash Offer holds (the 288 prod rows).
  - Evidence: S:243, S:98; D:143 BR-22.
- G6 Migrated Active Offers with no Glass model against RULE-03
  - Tag: ambiguous · Impact: high (state)
  - What happens: §8 allows an Offer with only a hint when the migration yields no Glass model (D-13 conflicts go to a review list). RULE-03 says an Offer cannot be Active without one, and §10 makes Active Offers advert candidates. How a migrated Active Offer without a model is treated is not stated: status kept, moved to Pending, or held until the review.
  - Evidence: S:129, S:244, S:258; D:472.
- G7 Migrated Sales and Reservations have no V1 data for the new fields
  - Tag: unknown · Impact: normal
  - What happens: a Sale records entered price, VAT rate, buyer's price and channel (FR-18). V1 stored only quantity (S:291 §12 row). Reservations need an author and note. "Plain Sales and Reservations" (§8) does not define their values. The data-migration spec may settle this; this spec does not say so.
  - Evidence: S:246, S:85, S:291.
- G8 Validation on edits after sending
  - Tag: ambiguous · Impact: normal
  - What happens: FR-06 (required fields) is defined for "sending for approval". An Active Offer that a seller edits goes to Pending automatically (FR-07), and §7 says "Rejected → Pending ... FR-06". Is the edit refused when a required field is missing (for example no photo)? Does an admin's edit of an Active Offer validate? What stops an admin saving price 0? Also, FR-06 requires price ≥ 1, but bulk price (FR-29) skips only ≤ 0, so a price between 0 and 1 is reachable. Price precision and the rounding of percentage changes are not defined.
  - Evidence: S:61–62, S:213, S:108.
- G9 Bulk actions other than price and approve
  - Tag: unknown · Impact: normal
  - What happens: bulk withdraw, delete and change Glass model (FR-31) have no rule for Offers in a wrong status (for example Reserved for withdraw and delete, a Sold Offer for withdraw) and no result message. FR-29 and FR-11 specify both.
  - Evidence: S:113, S:108, S:69.
- G10 FR-32 wording reads as an exclusion
  - Tag: ambiguous · Impact: normal
  - What happens: "the same tabs except Trash and bulk change price and withdraw" can be read as "no bulk change price and no withdraw". D-47 has the same wording. §3 gives the seller bulk price change but only a single withdrawal, so §3 and FR-32 are not aligned on seller bulk withdraw.
  - Evidence: S:114, S:45; D:511.
- G11 Announcements list lacks the events that end an advert
  - Tag: defect? · Impact: high (external)
  - What happens: §7 says adverts end on Active → Rejected and Active → Pending, but §10's list of announcements has no rejected or "sent back to Pending" event, only withdrawn and deleted. A channel could not know. D-51's list shares the gap, but the spec claims a complete behaviour. "Advert candidate" is also not defined.
  - Evidence: S:215–216, S:258; D:518.
- G12 Draft cannot be dropped by a seller
  - Tag: decide · Impact: normal
  - What happens: a seller cannot delete (D-43) and cannot withdraw a Draft (FR-24: Pending, Rejected or Active). A seller's abandoned Draft stays forever. An admin can delete it. No requirement says so.
  - Evidence: S:97–98, S:45.
- G13 Wording errors and undefined terms
  - Tag: confirmed · Impact: normal
  - What happens: §3 Seller row "release or sell a Reservation they or another seller-side made" has no meaning, because only the owner or an admin can reserve. §7 "Pending → Withdrawn: adverts end" (a Pending Offer has no adverts). The §7 table has no row for admin-owner Draft → Pending (only "(else Pending)" in the Draft → Active row). Not defined in the spec or glossary: "sold this month" (returned Sales? Trash?), the Inquiry "channel" value list (the Sale list is in §2), "last changed", "unchanged since". FR-09 says the owner gets a Journal entry "either way", which FR-34 overrides for an admin-owner.
  - Evidence: S:45, S:221, S:210, S:114, S:67, S:119.
- G14 Withdrawal reason and rejection reason storage
  - Tag: unknown · Impact: normal
  - What happens: FR-35 withdraws "with the reason 'seller deactivated'" and FR-09 requires a rejection reason. §8 Offer data has no field for either. Rejection reason may live only in the Journal entry. Whether the seller sees the reason on the Rejected Offer is not stated.
  - Evidence: S:123, S:67, S:232.

## Contradictions and defects
- R1 (Draft → Withdrawn), G1 (inquiries), G3 (Trash in statistics), G4 (chart data), G6 (Active without model), G11 (announcements) are the contradictions between sections or decisions that need a ruling.
- The FR-22 / FR-17 interaction (T1) is a latent contradiction. The owner accepts an Inquiry, which creates an admin-locked Reservation. The owner then cannot release or sell it (WF-04 error "owner tries to release or sell an admin's Reservation → refused").

## Leads outside scope
- Users: the offer stats on the user view (F-26, `ViewUser`) may belong to the Users spec. See R5.
- Integrations: whether Active → Rejected and Active → Pending events exist (G11) is for the WooCommerce grilling.
- Data migration: G5, G6, G7 may be answered in `.scratch/data-migration/offers/spec.md`. I did not read it.

## Could not establish
- Whether the data-migration spec answers G5–G7: not read, outside scope. A reading of that file settles it.
- Whether V1 sets the Reservation author on an accepted Inquiry to the sender (T1): V1 Inquiries had no sender (BR-17), so no V1 behaviour settles it. A business answer is needed.
- Whether the quoted labels in T5 exist in the translation files: not searched; V1 is read-only and the labels are V2 strings.

## Coverage ledger
- Searched: all of S, read in full; D sections C (F-01–F-34), I (IMP-01–07), J (ADD-01–05) and M (D-01–D-58) in full; D BR-17, BR-22, BR-24, BR-36, W-11 text; C §9, FR-04, FR-19, FR-20, FR-24, §11 edge case on twin vehicles and the traceability table; GLOSSARY.md Offers section. Grep of S for implementation words (filament, table, id, listen, registers, atomic, translation).
- Examined: `.scratch/offers/functional-spec.md`, `.scratch/offers/discovery/discovery.md`, `.scratch/catalogue/functional-spec.md`, `GLOSSARY.md`.
- Matched, not examined: `.scratch/data-migration/offers/spec.md` (cited by S §8, out of scope); `.scratch/domain/glass-offer-model.md` (reasons behind D-01–D-28, settled); the other discovery sections D, E, F, G, H (BR/W/Q bodies) read only where a trace looked doubtful. No V1 code was read.
