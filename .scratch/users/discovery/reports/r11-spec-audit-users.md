# r11 — spec-audit: Users functional spec

Question: Is `.scratch/users/functional-spec.md` a faithful, traceable, implementation-free spec of the agreed decisions? · V1 commit: 511ce3b

## Summary
The spec is largely faithful. All D-01..D-46 are reflected, and the decision counts on spec line 9 check out (preserve 3, correct 29, new 4, exclude 7, pending 1). Implementation leakage is minimal.
- Check 1: about 20 invented specifics found, mostly security-flavoured details of the reset/invitation links and the impersonation/Journal edge cases. None are covered by any D-.
- Check 2: one decision is reflected wrongly (D-18, the label of the "go to subject" button). Three more are partial: D-16 "deleted with the User", D-21 visibility, D-11 detail.
- Check 3: no IMP-/ADD- item lacks an approving decision. §16 omits the IMP-04, IMP-11 and IMP-13 pointers.
- Check 4: no class, table or framework names are used as requirements. Four borderline phrasings are listed.
- Check 5: one internal contradiction that touches D-09/D-23 (FR-25), several undefined terms, and a missing Permission list.

## Findings

### Check 1 — Requirements or details with no trace, or beyond the decisions

Each item below is tagged `inferred` or `decide`, never `confirmed` as a user decision.

1. **Reset and invitation links are "single-use"** (RULE-14, FR-26/WF-04 "single-use"). Tag: decide · Impact: high.
   - D-34 says only "60-minute link". No D- says single-use.
2. **Invitation links expire after 60 minutes** (RULE-14, WF-04, §2 "Invitation: time-limited"). Tag: inferred · Impact: high.
   - D-38 and D-41 say "same mechanism" as the reset (D-34), but never state an invitation expiry.
   - A 60-minute invitation sent in bulk to migrated Users (§8) is practically fragile. This is a real gap, not just trace hygiene.
3. **"The response is the same whether or not the address belongs to a User"** (FR-11, RULE-14, WF-03 step 2, edge case 10). Tag: decide · Impact: high.
   - This is an anti-enumeration rule. D-34, D-38 and D-39 do not state it.
4. **Setting a password through a reset link signs the User in** (FR-11, WF-03 step 4). Tag: decide · Impact: high.
   - D-34 does not say this. V1's reset (F-15) only sets the password.
   - Also invented: "Expired or used link: a message offering to request a new one" (WF-03).
5. **Deactivated User gets no reset link** (WF-03, edge case 10). Tag: inferred.
   - It is cited to D-39, which says only "cannot sign in".
6. **Rate-limit behaviour** (FR-09, WF-01). Tag: decide.
   - Invented: "refuses further attempts for the rest of the window with a message saying when to retry"; "wrong credentials ... counts against the limit"; the identification by e-mail plus IP.
   - D-37 gives only "for example 5 per minute per e-mail and IP". FR-09 keeps "for example", and RULE-13 gives no number. No threshold is ever fixed (see check 5).
7. **Failed-email-send handling** (§10). Tag: decide.
   - "Reported to the Admin who requested it, or shown to the Guest as the usual neutral response" appears in no decision.
8. **Nested impersonation forbidden** (FR-28, RULE-10, WF-06). Tag: inferred.
   - "Nobody can start impersonating while already impersonating" comes from V1 behaviour (F-06). D-22 does not decide it.
   - Also invented: target must be "active and their Role is registered" (WF-06 precondition).
9. **Role order for other modules' Roles** (§2 "Role order"). Tag: decide.
   - "Roles declared by other modules rank at the User level unless their module states otherwise" is cited to D-22/D-23. Neither says it.
   - The mechanism by which a module "states otherwise" is not defined.
10. **Journal entry is not created for a non-existent or deactivated User, and the writer's operation still completes** (FR-29, edge case 7). Tag: decide · Impact: high.
    - It is cited to D-16/D-39. D-39 says the Journal stays for a deactivated User. Neither decides that entries stop.
11. **Journal entry has a "link label"** (FR-29, FR-32). Tag: decide.
    - D-16 lists link only. D-18 says the button is named after the entry's *kind* (see check 2).
12. **Severity filter in the Journal** (FR-30). Tag: inferred.
    - D-28 says only "filters or tabs ... sort such events". D-16 does not mention a severity filter.
    - Filtering by kind and by severity is a spec choice.
13. **User list "active/inactive" filter and an inactive marker** (FR-22). Tag: inferred.
    - D-32 names filters by Role and account type only.
    - D-39 decides "konto nieaktywne" on "other screens", not an inactive-state list filter. D-46 covers only the password marker.
14. **"Admins" group = *active* Users** (§2, FR-29). Tag: inferred.
    - D-12 says "Users holding Admin or Super Admin". "Active" is added.
    - Likewise "WooCommerce setting picks an *active* User" (§9) is added to D-43.
15. **Confirmation before deactivation** (WF-05 step 1). Tag: decide. No D- decides it.
16. **Deactivated-while-impersonating rules** (WF-05 errors, edge case 11). Tag: decide.
    - "A deactivated User who is being impersonated loses that session too, and the impersonator returns to their own account" has no D-.
    - Edge case 11 says "An Admin who can impersonate is deactivated mid-impersonation → both sessions end", also without a D-.
    - The two rules are not contradictory but are undecided, and the sentences cover different targets.
17. **Message wording at sign-in** (FR-07, WF-01). Tag: inferred.
    - FR-07: "no valid role ... contact AllGlass". WF-01: "account is inactive".
    - D-20 says only "a clear message". The deactivated-User refusal is not specified in D-39.
18. **FR-21 Journal text changes V1's wording** (FR-21). Tag: ambiguous.
    - Spec: "Użytkownik {name} zmienił rodzaj konta na: Firmowe|Prywatne".
    - The F-02 text it claims to preserve reads "Uzytkownik {name} ..." (no diacritic, discovery.md:82). No D- corrects the spelling.
19. **Migration extras** (§8). Tag: decide.
    - "V1 Journal entries may be migrated as kind `offer` ... HTML stripped" has no D-. The spec itself leaves the choice to the migration plan.
    - The "Not migrated" list adds items beyond D-35/D-38: API tokens, sessions, remember tokens, profile photos, "Aktywność użytkowników" rows. Tokens and the feed are implied by D-34/D-28. Sessions, remember tokens and photos are undecided.
    - "Account type: Company if V1 had both the company flag and the declaration flag" is a derived rule. D-38 says only "for Users who had accepted the declaration".
20. **Audit events "creation, edits"** (§9 Audit, WF-04 step 4). Tag: inferred.
    - D-21, D-22, D-28, D-39 and D-46 name audit for specific events. Creation and edits are not stated, other than the existing change history (D-32).
21. **Reactivation of a Deactivated Admin and "Reset link re-send to Users and Admins"** (RULE-08, FR-26). Tag: inferred.
    - D-38 allows re-sending a *password-reset* link to Users an Admin manages. "Re-send an invitation" is an extension.
22. **Phone "optional in the admin form"** (RULE-02). Tag: inferred.
    - D-29 says only "phone required in the profile".

### Check 2 — Decisions not reflected, or reflected wrongly

1. **D-18 reflected wrongly** (FR-32 vs D-18). Tag: confirmed · Impact: normal.
   - D-18: the "go to subject" button "is named after the entry's *kind*".
   - Spec FR-32: "labelled after the entry's *link*", supported by an invented link-label field (FR-29).
2. **D-16 "Entries ... are deleted with the User"** has no counterpart. Tag: ambiguous.
   - RULE-12 and §8 say entries go only when the owner deletes them. D-39 (no deletion of Users) makes D-16's clause moot, but the spec neither mentions nor retires it.
3. **D-21 "Only a Super Admin sees and edits them [Extra Permissions]"** conflicts with FR-23/§3. Tag: defect?
   - FR-23's detail lists "Role and Extra Permissions" with no restriction on who sees them.
   - §3 and RULE-11 say an Admin may not *see* Extra Permissions.
   - D-21 also says the detail shows them separately, so the visibility limit on the detail is unspecified.
4. **D-23 "Super Admin's Role changes only through the console"** is contradicted by FR-25. Tag: defect? · Impact: high.
   - FR-25: "The Role section is shown unless the editor is editing themself". A Super Admin editing another Super Admin would therefore see a Role section. That section offers User or Admin (never Super Admin, FR-24).
   - That would let a Super Admin demote a Super Admin from the panel, against D-09/D-23.
   - Edge case 2 covers only Admin viewing a Super Admin.
5. **D-11 detail** is only partly carried. Tag: ambiguous.
   - Admin "accepts offers, sees everyone's offers, manages sellers"; Super Admin "additionally manages settings, API keys, the activity log and other admins". §2 summarises these as "daily operations" and "everything".
   - "Each module defines what Admin may do" is not stated in the spec.
6. **D-30 "For now; may change later"** is not carried. Tag: ambiguous. Minor.
7. **D-20 "Nothing is deleted automatically"** is not stated. Tag: ambiguous. Minor.
8. **D-32 "declaration acceptance date"**: the spec adds the version. Tag: inferred. Harmless (D-24).
9. **D-04 "a shared service ... with helpers"** is reflected only as the "Admins" group (§1, §14). Tag: confirmed. Acceptable.
10. All other decisions are reflected correctly: D-01..D-03, D-05..D-10, D-12..D-14, D-17, D-19, D-22, D-24..D-29, D-31, D-33..D-46. D-15 is correctly treated as superseded.

### Check 3 — IMP-/ADD- items without an approving decision
- None found.
- **ADD-01** has D-46. **ADD-02/03/04** appear only in §15 as pending (D-45). They are not specified as requirements.
- **IMP-02** has D-41. **IMP-14**'s accepted part (show the Otomoto text on detail) has D-32. The account-type part is rightly absent (D-30).
- **Traceability gap** (check 3 adjunct), tag `confirmed`: §16 omits IMP-04 (D-10, D-11), IMP-11 (D-29) and IMP-13 (D-35).
- §16 cites IMP-14 in the FR-24/FR-25 row, but FR-24 and FR-25 do not realise it; D-32 and FR-23 do.
- FR-26 cites only G-08, a gap, not an F-/BR-/W- item. It is approved by D-38/D-46.

### Check 4 — Implementation leakage
No class, table or framework names are used as requirements. Names appear only in the §12 and §14 legacy traces (Jetstream, `/api/user`, `profile/{record}`), which is allowed. Borderline phrasings:
1. **FR-28 / §12 "wildcard"** is a pattern term from D-09. Say "passes every check".
2. **FR-02 "when the application starts"** fixes a registration mechanism (from D-10). The requirement is only that registration precedes use.
3. **Journal kind values `offer` and `users`** are code-style identifiers. They are acceptable as contract values but should be marked as such.
4. **"Console command" (FR-05/RULE-09)**: this is the D-09 decision itself, so it is acceptable.

### Check 5 — Places a reader without V1 could not act

1. **Permission list missing.** Tag: decide · Impact: high.
   - §3 says every action is guarded by a Users-declared Permission. The spec never lists those Permissions or states which built-in Role holds which.
   - The registry cannot be implemented without that list, and the "Admin may" column is not a per-Permission grant list.
2. **FR-25 versus FR-24/WF-04 on Role choice** (see check 2 item 4).
   - FR-24 and RULE-08 limit Admin to Role User or Admin. WF-04's precondition adds "or a Role from another module".
   - Whether Admins may assign other modules' Roles is undecided.
3. **Rate limit has no number or window** (RULE-13, FR-09, "for example 5 per minute"). A reader cannot test it.
4. **Declaration "version"** is undefined. Tag: decide.
   - What identifies the current text version is unspecified. So is whether a new wording invalidates existing acceptances.
   - Migrated Users get "V1", but no V2 first version is named.
5. **Role order for module-declared Roles**: the "states otherwise" mechanism is undefined (check 1 item 9).
6. **"Announced to other modules"** has no stated guarantee. Tag: decide.
   - Not stated: delivery, behaviour when no module reacts or reacts late, ordering. WF-02 says "A reacting module's failure does not undo the change" but gives no retry or visibility rule.
7. **Field-level constraints are missing.** Tag: decide.
   - No format or length rules for NIP, phone, postal code, address, city, shipping text or the generator. RULE-04 requires NIP and address but defines no valid NIP.
8. **Impersonation edge cases.** Tag: decide.
   - May the impersonator change the target's Account type, profile or Journal? D-16 says "only its owner reads it", and FR-27 says only "the impersonator sees and acts as the target".
   - Only the forced-password screen is covered.
9. **Reset link while mail is unavailable.** Tag: decide.
   - The "Nie pamiętasz hasła?" link is unconditional (FR-11), but WF-03's precondition is "e-mail sending works". The UI state when mail is down is undefined.
10. **Bulk invitation of migrated Users** (§8). Tag: ambiguous.
    - The 60-minute single-use link makes mass migration awkward (Admin must re-send for most Users). Procedure, timing and the resulting state of un-activated accounts are unspecified.
11. **Undefined terms.** Tag: ambiguous.
    - "Audit log", "registered Permission" and "announced" are used without definition. They are not in the §2 glossary list, and the audit log is the only one of the three that has an owner (X-06).
    - "Seller declaration" and "Journal" are in GLOSSARY.md; the others should be added there or in §2.
12. **Super Admin deactivation.** Tag: ambiguous.
    - Can a Super Admin deactivate another Super Admin? RULE-08 and D-39 forbid only Admins. WF-05's precondition implies yes but never states it.
13. **User created by Admin: first Journal entry or migration notifications.** None. Not an issue.

## Contradictions and defects
- **FR-25 vs D-09/D-23** (check 2 item 4): a Role section would be shown to a Super Admin editing a Super Admin. Tag: defect?
- **FR-23 vs §3/RULE-11** (check 2 item 3): visibility of Extra Permissions on the detail. Tag: defect?
- **FR-32 vs D-18** (check 2 item 1): wrong button-label rule. Tag: confirmed.
- **WF-05 vs edge case 11** (check 1 item 16): different impersonation-deactivation outcomes for different targets. Not a contradiction, but both are undecided.

## Leads outside scope
None.

## Could not establish
- Whether the unattributed details in check 1 reflect unrecorded user answers. D-46 says "accepted all recommendations", so some may be real but unlogged. This can only be settled by the manager or the user checking the grilling transcript.
- I did not read discovery sections D-L in full. Only sections C, J and M were read. I read section I (IMP list) and grepped for the "Pamiętaj!", "Rozumiem" and "zablokowana do" strings, which confirmed FR-18/FR-19 against BR-14 and W-01 (discovery.md:272, 302). I did not read V1 code.

## Coverage ledger
- Searched: `Pamiętaj`, `Rozumiem`, `zablokowana do`, `single-use`, `jednorazow`, `IMP-04|IMP-11|IMP-13` in the spec, and Glossary term names.
- Examined:
  - functional-spec.md, lines 1-591, in full.
  - discovery.md sections C (lines 42-250), I (503-521), J (522-527) and M (586-666).
  - GLOSSARY.md (grepped for the terms Deactivated User, Extra Permission, Account type, Journal).
- Matched, not examined: discovery sections D-H and K-L, V1 code. They are out of scope for this audit.
