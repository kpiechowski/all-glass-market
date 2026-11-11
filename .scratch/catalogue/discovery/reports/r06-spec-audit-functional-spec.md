# r06 — spec-audit: Catalogue functional spec

Question: Is `.scratch/catalogue/functional-spec.md` a faithful, traceable, implementation-free functional spec of the decisions taken? · V1 commit: 511ce3b

## Summary
The spec is largely faithful: all 53 classed decisions (D-01..D-58 touching Catalogue; D-28 is process) were checked one by one and nearly all are carried correctly. Check counts: 1 = 9 findings, 2 = 8, 3 = none blocking (2 notes), 4 = 4 minor, 5 = 14.
Top issues:
- FR-02/WF-03 add an unapproved "warn how many offers are affected before saving" rule.
- The seller table says sellers may not see withdrawn models, which contradicts FR-24 and D-44 (a withdrawn model stays visible wherever used).
- FR-12 says "no status" while FR-13 and §8 give dictionary values a withdrawn/active state.
- The §8 header counts are wrong (preserve is 4, not 5).
- §8 points at `.scratch/data-migration/catalogue/spec.md`, which does not exist.
- Several user-visible Polish labels are not in any source.
- Several rules are too vague to implement: "looks like a Eurocode", the offer count and "in use" semantics, the vehicle-name proposal, and moving a main Eurocode.
Tag: all findings below are `confirmed` (read directly against spec and discovery text) unless marked otherwise.

## Findings

### Check 1 — requirements with no (or insufficient) trace
1. FR-02 and WF-03 step 2: "Before saving a parameter change, the admin is told how many offers it affects." Cited D-01, D-09, D-37. D-37 only says the offer count appears as a link on the list; D-44 only requires a warning when deleting a vehicle. No decision, F-, BR-, W- or domain-model text has this pre-save warning (grep of discovery and glass-offer-model.md). Untraced new requirement.
2. FR-08 "V1's slug is dropped" for vehicles. Trace BR-02 only describes V1 (the car slug). D-32 drops slugs for dictionaries only; D-31/D-34 are silent on the car slug. Unsupported (probably intended, needs a decision).
3. FR-09 tagged [Preserve], and "see for each how many Glass models it fits". F-01 and D-34 mention neither the count nor a preserve decision. The only count decision is the D-44 warning on delete. No D- is class "preserve" for the vehicle list.
4. WF-01 step 4 and §7 table row "(new) → Active … announced as changed". D-51 lists change, withdraw and restore events and says only "no 'deleted' event"; it does not say creation raises "Glass model changed". Extension.
5. §7 row Withdrawn → Active "announced", and WF-04 "changing or withdrawing a producer, is announced". D-51 has "Producer changed / withdrawn" but no "restored" event for producers. Restore-announcement for a producer is unsupported; the table also says "announced" for all dictionary values, while D-51 covers only Glass models and producers.
6. §9 Audit bullet "changes to catalogue entries are recorded in the change history" and §8 "change history (Audit)". Trace X-06 is a V1 fact ("V2 Audit decides its own scope"), not a decision. No D- decision requires it.
7. §10 "A failure in an integration never blocks a catalogue change". Trace F-11, F-15, X-03, D-05, D-26, D-43; none says this. Untraced.
8. §12 row 8 "approval never used (all values active)": the evidence is G-09/r06-data-shape (not cited); D-25 does not give this reason. §10 "OLX later" is supported only by `.scratch/roadmap.md`, not by any decision or discovery item.
9. RULE-03 "spaces and other characters removed". D-03 says "no spaces"; D-41 says "letters and digits only" but not whether other characters are removed or refused. The spec picks removal (tag `inferred`). The over-long case: WF-01 says "refused", D-41 does not say refuse or truncate.
Minor: FR-05 admin-list filters and "withdrawn models only on request" are traced to D-49 (candidate search), not to the admin list (D-30). Extension, plausible.

### Check 2 — decisions not reflected, or reflected wrongly
- D-01, D-03, D-04, D-05, D-07, D-08, D-09, D-11 (partly), D-12, D-13, D-14, D-15, D-16, D-17, D-18, D-20, D-24, D-25, D-26, D-27, D-29, D-31, D-33, D-34, D-36, D-38, D-39, D-40, D-41, D-42, D-43, D-44, D-46, D-47, D-48, D-49, D-50, D-52 (but see 2c), D-53, D-54, D-55 (but see 2h), D-56, D-58: reflected correctly.
- (a) D-28 is cited in §1 as the source of "V1 had no catalogue". D-28 is the process order (Catalogue grilling → spec → Offers …). Wrong citation. The header's "(…, D-28)" for inputs is closer but also not what D-28 says. The statement actually comes from discovery §B and D-23.
- (b) D-23 is cited in §14 for "a make → model → generation tree … later option". D-23 is the one-module decision. The "later option" wording is in discovery ADD-03 (which itself cites D-23), so the citation is inherited, but it is not what D-23 says.
- (c) D-52 / D-44 conflict: §3 says the seller may not "see offer counts or withdrawn models". D-52 says no withdrawn models in the picker, but D-44 says withdrawn entries stay visible wherever already used. The seller's own offer may use a withdrawn model; the spec does not say what the seller sees there. See also check 5.
- (d) D-30: "A Eurocode has no screen of its own" is not stated. It is only implied by FR-03.
- (e) D-35: "migrated models without a position take it from deleted offers, else the review list". §8 says "from its V1 offers; models with no position after that go to the review list" (reading differs: live offers are already the source in D-09). The "deleted offers" nuance is lost.
- (f) D-45: "Combined values such as 'PRZEDNIA OPUSZCZANA PRAWA' are not split" is not stated; §8 only says "migrate as they are". Weak but compatible.
- (g) D-19: "An Offer cannot be approved without a Glass model" is not stated (FR-20 says approval belongs to Offers). D-10 (offer without a model has only a hint) likewise absent. Both are Offers-side, so low impact.
- (h) D-55: "No uploading of custom SVGs" and D-57 "a mapping change raises no event" are not stated; RULE-12 implies the second only by saying a mapping change "is not a change of any Glass model". Add explicitly. Also D-25 "position not a code enum" is not stated.
- (i) D-11: "No reason or source on a Eurocode for now" absent. Low.
- Header: "preserve 5 · correct 27 · new 14 · exclude 7 · pending 1" = 54. Counting the Class lines in section M gives preserve 4 (D-27, D-45, D-46, D-47), correct 27, new 14, exclude 7, pending 1 = 53 (D-28 is "n/a"). The preserve count is wrong, or D-28 was counted as preserve.
- Discovery staleness the spec silently overrides (not a spec error, but a reader comparing will be confused): IMP-03 still says "decision: open" and ADD-06 describes only two mappings; D-44 and D-56 supersede.

### Check 3 — IMP-/ADD- items without an approving decision
None without a decision. IMP-01 (D-03, D-41), IMP-02 (D-15), IMP-03 (D-44; discovery text stale), IMP-04 (D-17), IMP-05 (D-14, D-24 partly, D-49), IMP-06 (D-26), ADD-06 (D-54–D-57). Notes:
- IMP-05 is "partly" decided; offer-list filtering is deferred to Offers. The spec covers only the Catalogue half (§9), which is consistent.
- ADD-01 and ADD-03 are approved additional functionality (D-01, D-04, D-07 and D-31, D-34) but §13 "Approved additional functionality" lists only ADD-06. They appear only in §16. Inconsistent labelling.

### Check 4 — implementation leaking in
Mostly clean: framework, class and table names appear only in the §12 "V1 behaviour" column (legacy traces). Minor leaks:
- §9 "through its public module interface" (the D-24 `ModuleApi`) and "listeners" are architecture words used in a requirement. Suggest "Catalogue offers the following to Offers".
- §8/§9 "Audit" is a module name and "change history (Audit)" is architecture-flavoured.
- FR-25 "room for sections added by integrations" and "view of a catalogue entry" is UI-structure language (D-43 infolist), acceptable but abstract.
- FR-04 "full-width cards" and FR-19 "search window" are UI layout, but D-53 and D-52 are decisions about exactly this, so they are legitimate requirements.
- §8 points to `.scratch/data-migration/catalogue/spec.md`, which does not exist (`ls` failed). Dangling reference; §15 depends on the untracked `zweryfikwoania_klientem.md`.

### Check 5 — places where a reader who never saw V1 could not act
Contradictions inside the spec:
- FR-12 "A value has only a name … no status" and §12 "Name only" versus FR-13, FR-24 and §8 "name; withdrawn or active" (a status). D-32 vs D-44 inherits the tension; the spec should say "no approval status, but a withdrawn flag".
- §3 (seller may not see withdrawn models) versus FR-24 and §11 (withdrawn stays visible where used). Undefined: what a seller sees on their own offer whose model is withdrawn.
- FR-24 "Active or Withdrawn → deleted … no announcement needed" versus FR-14/§11 rename announcements: consistent, but rename of a colour or equipment value announces nothing at dictionary level (only per Glass model); say so.

Undefined terms and vague rules:
- Polish labels in §2 ("Pozycja", "Wycofany", "Miniatura", "Kod OEM") are not in GLOSSARY, the domain model or discovery; "Wyposażenie" and "Kolor" are. These UI words are invented and need agreement. "Wycofany" is the user-facing label for D-44's "withdrawn" and nothing confirms it.
- "parameter change" (FR-02, WF-03 step 2, D-51): not defined. WF-03 step 1 lists position, colour, equipment, OEM codes and fitment; is OEM or fitment a "parameter"? Do those trigger the offer-count warning?
- "offers refer to / use" (FR-02, FR-24, WF-04): do soft-deleted, sold or archived offers count? D-35 mentions deleted offers, so V2 may have them. Undefined.
- FR-20 "when it looks like a Eurocode": no rule (length, characters, pattern). Inherited from D-38 but cannot be implemented.
- FR-08 proposed name `MAKE | MODEL | BODY | years | ARGIC code`: format for empty fields, open-ended years ("to may be open") and separators is not stated. RULE-09 does not say whether name uniqueness ignores case (RULE-08 does for dictionaries).
- FR-07: no validation of years (from ≤ to, range) or generation format; model (free text) vs the list-based make and body; whether the ARGIC code has to be unique.
- WF-02: moving a model's main Eurocode is not covered. Is it refused like removal? When a code moves, does it become non-main in the target (presumably yes)? Can the target model be a withdrawn one?
- WF-02 and RULE-01 across withdrawn models: a Eurocode of a withdrawn model still blocks reuse? Implied but not stated.
- Withdrawn entries: can the admin edit a withdrawn Glass model, or save a Glass model whose colour or equipment is withdrawn without changing it? Not stated.
- FR-05 / WF-06 / §9 candidate search: minimum fragment length, result limit or paging, order within a group, and what happens when one model matches by several routes (shown once under the first group?) are all unspecified. V1 used ≥3 chars and 20 results; neither is carried or dropped explicitly.
- FR-16 "opacity" (range, unit); FR-15 "mirrored … for left and right variants": which side is the drawn one is not stated; the pictogram set and shape artwork are "shipped" but not enumerated (acceptable if delivery-level).
- "Identification hint" is used in WF-06 and FR-20 and is defined in GLOSSARY (line 107) but not listed in §2's local terms; the same goes for "Approval" and "Advert".
- WF-01 preconditions "none" (no actor or permission precondition); WF-02..WF-06 lack explicit preconditions and postconditions for several flows (WF-04 has no pre/postcondition for withdraw of a vehicle-referenced value).
- §8 migration list: "producer duplicates and unused producers go to the review list" is stated, but who resolves, the format and what migrates in the meantime are in the missing data-migration spec.

## Contradictions and defects
See Check 2c and Check 5. The header decision-count mismatch (preserve 4 vs 5) and the dangling migration-spec path are factual errors in the spec.

## Leads outside scope
- Discovery IMP-03 ("decision: open") and ADD-06 (two mappings, "if accepted" wording in D-48) are stale against D-44 and D-54–D-56. Worth a tidy in the discovery.
- D-10 and D-19 (offer without a model has only a hint; no approval without a model) need to land in the Offers spec.
- OLX appears only in `.scratch/roadmap.md`; no decision.

## Coverage ledger
- Searched: grep in discovery, glass-offer-model.md, GLOSSARY.md, `.scratch/` for "how many offers", "OLX", "89", "hint", Polish labels (Wycofany, Pozycja, Miniatura, Kod OEM, Wyposażenie), "7814/8613", "all values active"; class counts of section M; existence of `.scratch/data-migration/catalogue/`.
- Examined: `.scratch/catalogue/functional-spec.md` (all); discovery sections C, D (BR/Q refs), E, F, H, I, J, M (all D-01..D-58); GLOSSARY "Glass catalogue"; glass-offer-model.md (only via grep, lines 50, 161).
- Matched, not examined: `.scratch/offers/discovery/reports/r06-data-shape.md` (source of data facts); `.scratch/roadmap.md`; `zweryfikwoania_klientem.md` (referenced by §15, not read); V1 (out of scope; `.claude/skills/module-discovery/missions.md` not found at the V1 root, so the spec-audit "done when" came from the brief).
