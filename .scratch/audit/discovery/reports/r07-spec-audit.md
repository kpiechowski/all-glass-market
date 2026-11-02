# r07 — spec-audit: Audit functional spec

Question: Is `.scratch/audit/functional-spec.md` faithful, traceable and implementation-free? · V1 commit: 511ce3b

## Summary
The spec is largely faithful and well traced. All 34 decisions are accounted for, and the header counts are correct (correct 16, new 10, exclude 4, pending 1, three process records). No IMP-/ADD- item lacks an approving decision. Implementation leakage is small.
Main problems:
- Several behaviours are stated as requirements with no decision behind them. They are listed under check 1 and need a decision or a "proposal" label.
- One dangling reference: the data-migration spec does not exist yet.
- Terminology differs from the Users spec ("security channel" vs area "bezpieczeństwo"; "audit log" vs Change history).
- Several gaps would stop a reader who never saw V1 from implementing the Technical log page and Restore.
- The Users spec §9 Audit bullet is consistent with the Audit spec (see "Users spec §9 consistency").

## Findings

### Check 1 — requirements with no trace to an F-/BR-/W- item or a D- decision
- **1.1 Atomic recording** · Tag: `decide` · Impact: high (state)
  - WF-01 says "if the entry cannot be stored, the change is not committed either".
  - No D- decision, BR or W- item says this. D-07 says only that everything is recorded.
  - Evidence: spec WF-01 "Errors and exceptional scenarios", RULE-02. discovery.md M D-07 has no failure semantics.
- **1.2 Restore confirmation** · Tag: `decide` · Impact: high
  - FR-14 and WF-02 require a confirmation dialog listing the fields and values.
  - D-16 and D-11 do not mention confirmation. V1's F-05 had none. Only F-10's revisions page confirmed.
  - The traces cited (W-02, W-05, D-11, D-16) do not support it.
- **1.3 Restore marker text** · Tag: `decide` · Impact: normal
  - FR-14 and WF-02 say the Restore entry is "marked" with "przywrócono z wpisu z {data}" and keeps the source entry.
  - D-16 says only "is itself recorded". The text and the source link are not in any decision.
- **1.4 Which entries offer Restore** · Tag: `inferred` · Impact: normal
  - FR-13 and §14 say a **utworzono** or **usunięto** entry offers no Restore. §14 cites D-16 for "Restore of deleted records, Restore of creation entries".
  - D-16 does not say this. It is inferred from V1 F-05 (the old-values action was hidden on created entries) and the "exactly the fields in the entry" wording.
  - WF-02 also says "the record has been deleted → Restore is not offered". That is also unrecorded.
- **1.5 Readable form stored at recording time** · Tag: `decide` · Impact: high (data)
  - FR-08, RULE-07, FR-02 and §8 "Restore data" require values to be captured as readable text when the change happened. A later rename or removal does not alter the entry. Both readable and writable forms are kept.
  - D-14 says only that a resolver shows references by readable name. D-11 says storage must make restore "straightforward".
  - This is a real design choice (snapshot vs resolve at display) presented as a requirement with a D-14 trace.
  - RULE-07 "never as raw ids" also goes beyond D-14, which names producer and "the same for every relation".
- **1.6 usunięto holds no field values; utworzono holds initial values** · Tag: `inferred` · Impact: normal
  - FR-02. D-18 only says the deleted entry keeps the last title. BR-03 says a V1 create is logged even with no fields.
- **1.7 Cleanup schedule and failure handling** · Tag: `decide` · Impact: normal
  - WF-03 says cleanup runs "on the daily schedule". A failed cleanup is "logged as an error in the application channel and retried on the next run".
  - D-01, D-13 and D-30 require scheduled removal but give no frequency and no failure rule. G-04 only says the scheduler runs every minute.
- **1.8 Permission names and defaults** · Tag: `decide` · Impact: high (permissions)
  - "historia rekordu", "dziennik audytu" and "logi techniczne" are not named in any decision.
  - D-12 names three access levels "by Permission". Names and the Admin default for "historia rekordu" are spec-invented.
  - D-11 and D-16 say Restore is "guarded by a Permission". The spec has no Restore Permission and writes "Super Admin only". Confirm that a hard Role rule is intended and not a Permission with a Super Admin default.
- **1.9 Page and section labels** · Tag: `decide` · Impact: normal
  - "Historia zmian", "Dziennik audytu", "Logi techniczne" and "Przywróć" are not in any decision. Only "Nieudane zadania" (D-31) is.
  - "Przywróć" is derived from V1 "Przywróć nowsze/poprzednie". The others are fine as proposals but are not marked as such.
- **1.10 Traceability table vs inline traces** · Tag: `confirmed` · Impact: normal
  - §16 adds Q-11/IMP-03 to FR-04, Q-12 to FR-17 and IMP-01 to FR-03 that the inline traces omit.
  - RULE-01 (D-14, D-23) and the §3 permission table are not in §16.
  - Cosmetic only.

### Check 2 — D- decisions not reflected, or reflected wrongly
- **2.1 D-11 vs D-16 not reconciled** · Tag: `confirmed` · Impact: normal
  - D-11 says restore is "only for admins … guarded by a Permission". D-16 narrows it to Super Admin.
  - The spec follows D-16 and cites both, but never says D-11 was superseded. A reader of the discovery log sees a conflict.
- **2.2 D-23/D-27 area name** · Tag: `confirmed` · Impact: normal
  - D-23 and D-27 name the area/file "bezpieczeństwo". The Users spec §4/§9 uses "area 'bezpieczeństwo'".
  - The Audit spec writes only "security channel" (FR-15, FR-17, §2). The authoritative Polish identifier is missing.
  - Also, D-28 says "areas" are channels. The spec says "Channel" for "area". Add the mapping.
- **2.3 D-22 "one example only"** · Tag: `confirmed` · Impact: normal
  - §9 Offers goes slightly beyond "one example (Offers) only". It lists fields (type, producer, colour, eurokody, wyposażenie, zdjęcia) and Restore on price and status.
  - It is labelled "example … final list belongs to Offers discovery", so this is acceptable. The Users bullet names the marker, which is backed by D-23.
- **2.4 D-20 vs ADD-02** · Tag: `confirmed` · Impact: normal
  - The spec matches D-20 (no export, no "field" filter).
  - Discovery J ADD-02 still lists "field" and "export". The J text is stale and does not say it was superseded.
- **2.5 D-27 vs ADD-04** · Tag: `confirmed` · Impact: normal
  - The spec follows D-27. ADD-04 also listed 2FA changes, permission grants and file uploads. These are dropped silently.
  - §14 should state "2FA, permission-grant and upload events are not logged". Permission grants are Change entries via Users, which is fine.
- **2.6 IMP-05 stale** · Tag: `confirmed` · Impact: normal
  - Discovery I IMP-05 says "technical log pending". D-30 settled it (14 days).
- **2.7 FR-04 class** · Tag: `confirmed` · Impact: normal
  - FR-04 is tagged [Correct], but its impersonator-as-Actor clause is D-15, class `new`.
- **2.8 FR-09 and §12 state V1 delete buttons as fact** · Tag: `defect?` · Impact: normal
  - Spec: "no filters and with delete buttons". Discovery F-02 is `med` confidence. G-02 (does read-only mode hide Delete on the offer tab) was never resolved, only marked moot.
  - F-06 says delete exists on "both tabs". The offer-tab claim should be softened, or marked V1-unverified.
- **2.9 FR-14 "no permission check"** · Tag: `defect?` · Impact: normal
  - F-05 says "anyone who sees the detail" could roll back. The offer tab is admin-only (F-02), so "no extra permission" is accurate, but "no permission check" overstates.
- **2.10 Decisions fully and correctly reflected** · Tag: `confirmed`
  - D-01, 02, 03, 05, 06, 07, 08, 09, 10, 12, 13, 14, 15, 17, 18, 19, 20 (filters), 21, 24, 25, 27 (list), 28, 29, 30, 31, 32 (§15), 33 (every item of the contract is in FR-07), 34 (§13).
  - D-04, D-26 and D-34 are process records, correctly excluded from the counts.
  - Class counts re-derived from M: correct = 01, 05, 06, 07, 08, 10, 11, 12, 13, 14, 16, 18, 19, 22, 23, 25 (16). New = 02, 03, 15, 20, 27, 28, 29, 30, 31, 33 (10). Exclude = 09, 17, 21, 24 (4). Pending = 32 (1).
  - Evidence: discovery.md:364-397.
- **2.11 Missing from the spec** · Tag: `ambiguous` · Impact: normal
  - BR-02: in V1, deleting a User emptied the actor of their entries. The spec does not say what is shown for the Actor of a deactivated User.
  - D-18 and RULE-04 imply history outlives records, but a User is a record. State the display (name stays).

### Check 3 — IMP-/ADD- items in the spec without an approving decision
- None. Result: IMP-01..IMP-07 map to D-07/D-14, D-14, D-08, D-12, D-01/D-13, D-25 and D-10. ADD-01..04 map to D-03/D-33, D-20, D-02/D-28..31 and D-27, with D-34 as the selection.
- Related, not IMP/ADD: items 1.1-1.9 above are behaviours without a decision.

### Check 4 — implementation leaking in
- **4.1** · Tag: `confirmed` · Impact: normal
  - FR-08 and §8 "Restore data" prescribe a storage approach: "stored in readable form at recording time", "kept in a form the owning module can write back". WF-01 uses "stores", "committed".
  - Restate as observable behaviour: the history shows the value as it was, and Restore can write it back. Leave storage to design.
- **4.2** · Tag: `confirmed` · Impact: normal
  - FR-15, FR-21 and D-28 describe "daily files per channel … configuration". Those are decision-level product choices (D-02), acceptable as requirements. "Download the day's file" also depends on that.
  - "Configuration" is an implementation hint but is dictated by D-28. Keep it, tied to D-28.
- **4.3** · Tag: `confirmed` · Impact: normal
  - "ADR 0004" is cited as trace in RULE-03 and §9. It is a design record, not an F-/BR-/D- item. It is acceptable only as a legacy/design reference, so mark it as such.
- **4.4** · Tag: `confirmed` · Impact: normal
  - Clean: no class, table, framework or package names in requirement text. Terms like "queue", "Permission", "scheduled cleanup" and "stack trace" are domain terms.
  - "registers" and "registration" are the D-03/D-33 contract, a product concept.

### Check 5 — places where a reader who never saw V1 could not act
- **5.1 Technical log page** · Tag: `unknown` · Impact: normal
  - Not defined: level names and their order; message "shortened" to what length; entry ordering (newest first?); paging; the default channel and day on opening; what shows when there is no file for the day; search scope (message only, or context too); the download format; and the time zone of timestamps.
- **5.2 Security channel contents** · Tag: `ambiguous` · Impact: normal
  - FR-17 lists "password reset" without saying requested, completed, or both.
  - Does "sign-in" include the start of an impersonated session? Which fields does each entry carry (User, IP, time)? Only failed sign-in is said to carry the IP.
- **5.3 Restore and relations** · Tag: `ambiguous` · Impact: high
  - FR-13 and FR-07 speak of "fields". It is unsaid whether tracked relations (eurokody, zdjęcia) can be restored or whether a mixed field-plus-relation entry restores only its fields.
  - "Fields the module allows to be restored" can leave an entry with no restorable field. The action is then hidden (WF-02 precondition), but no message is defined.
- **5.4 Registration changes over time** · Tag: `unknown` · Impact: normal
  - What does an old entry show when a field is later excluded or relabelled, or a type is unregistered? Not specified. The central page's filter by "module or record type" has no stated source of values.
- **5.5 Display strings** · Tag: `ambiguous` · Impact: normal
  - "System: {reason}" and the other labels mix English and Polish. The UI language of fixed strings (e.g. "System", "Retry", "Remove" for failed jobs, level names) is not given. The Settings label for the retention period is not named.
  - The 365 days are not defined as running from the entry time (assumed).
- **5.6 Failed jobs** · Tag: `ambiguous` · Impact: normal
  - "Retry" and "remove" are described. Confirmation, and what happens when a job is retried while already retried, are not.
  - The tab shows the job name, which for a reader with no V1 knowledge is not a business name.
- **5.7 Reasons for System actions** · Tag: `ambiguous` · Impact: normal
  - FR-04 examples ("Import WooCommerce", "Harmonogram") are Polish labels. Who defines the list, and whether it is free text or fixed, is unstated.
- **5.8 Dangling reference** · Tag: `defect?` · Impact: normal
  - §8 points to `.scratch/data-migration/audit/spec.md` ("decided there"). That file does not exist. The import question is therefore open and unowned.
  - Evidence: `ls .scratch/data-migration/audit` fails.
- **5.9 Glossary** · Tag: `confirmed` · Impact: normal
  - The five terms listed in §2 exist in GLOSSARY.md with matching meaning (Actor, Change history, Change entry, Restore, Technical log). The local concepts (Auditable record type, Kind of change, Channel) are defined in §2 only.
  - "Dziennik audytu" as the page title sits close to the glossary's avoid-list entries ("dziennik zmian", "activity log"). Consider a note in the glossary.

## Users spec §9 consistency
- Tag: `confirmed`. Consistent, with two terminology points.
- Consistent items:
  - The User is a registered auditable type.
  - Changes covered: creation, edits, (de)activation, Account type, Extra Permission grants (relation changes), the "password change required" marker. The password itself is not audited (D-17).
  - Impersonation, sign-in, failed sign-in, sign-out and password reset go to the Technical log "bezpieczeństwo" (D-23, D-27).
  - The user detail shows the history read-only (D-21).
  - Every entry carries its Actor.
  - The amendment line cites Audit D-14, D-21, D-23, D-27.
- Evidence: users spec lines 452-458, WF-06 351-355, WF-07 369, FR-23 157; Audit spec §9, FR-11, FR-17.
- **Terminology** · Tag: `defect?` · Impact: normal
  - The Users spec uses "audit log" in the scope list (§1), FR-06, FR-21, WF-02 step 3, WF-04 step 4, WF-05 and §7. The Audit spec and glossary use "Change history".
  - FR-06 "Every grant and revocation is recorded in the audit log" fits the Change history. Normalise it.
  - The Users spec says "area", Audit says "channel" (see 2.2).
- **Users §9 gives Audit a list of audited actions the Audit spec delegates** (D-22). Acceptable because Users is the owning module.
- **Users §4.6 reference** · Tag: `confirmed`. Users spec §4.6 is "Journal", so the Audit spec §1 pointer is correct.

## Contradictions and defects
- D-11 vs D-16 on who may restore (resolved in the spec toward D-16, but not noted). See 2.1.
- Discovery section C table rows G-01..G-08 and some F- rows have fewer cells than the header (e.g. G-01 has 5 cells, not 7; F- rows have extra "Entry points" placement). The table columns appear shifted. Cosmetic, in the discovery, not the spec.
- Discovery J/I text stale vs decisions (2.4, 2.5, 2.6).

## Leads outside scope
- Users spec FR-21 and WF-02 say the Account-type change is "recorded in the audit log". Verify that Users' own registration (Account type field) matches.
- Data-migration spec for Audit is missing (5.8).
- Offers discovery must settle the Restore field list (price, status) and the relations list.

## Could not establish
- Whether V1 offer-tab Delete was visible at runtime (G-02). Needs runtime.
- Frequency of the cleanup, level names and truncation length. These need a business answer.
- I did not check V1 code. This mission is limited to the spec against the discovery.

## Coverage ledger
- Searched: spec FR-/RULE-/WF-/ADD-/D- ids against discovery.md sections C, J, M and the I/G sections they cite; class counts recomputed from M; "audit"/"security"/"Technical log"/"Change history" in the Users spec; glossary terms.
- Examined: `.scratch/audit/functional-spec.md` (all); `.scratch/audit/discovery/discovery.md` lines 41-175, 286-398 (C, D, I, J, K, L, M, Q-12); `.scratch/users/functional-spec.md` (Audit bullets and WF-05..WF-07 regions); `GLOSSARY.md` (5 terms); `.scratch/data-migration/audit` (absent).
- Matched, not examined: discovery sections E-G (W-/Q- bodies other than Q-12) and the r01..r06 reports. Only ids cited in the spec were checked for existence.
