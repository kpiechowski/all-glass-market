# 04 - Own profile, Account type, Seller declaration, lock, announcement

Status: resolved
Blocked by: 02, 05

Spec: FR-13..FR-21, RULE-01..RULE-07, WF-02, §7 Account type table, §11 Account-type cases. Decisions: D-24..D-30, D-47.

## What

- **"Mój profil" (FR-13..FR-16):** the fields from FR-14, the 20-character password generator, the "i" navigation badge while not Company, and no Otomoto shipping text.
- **Account type (FR-17, RULE-04, RULE-05):** one choice, Private or Company.
  - Company needs NIP, address and an accepted Seller declaration. Record the acceptance date and the text version; the current text is version "V1" (D-47).
  - Switching to Private clears the acceptance.
- **Notices (FR-18):** the corrected private-account notice (VAT only, nothing about visibility) and the "Pamiętaj!" modal.
- **Lock (FR-19, RULE-07):** 30 minutes after a saved change, with the "Zmiana rodzaju konta jest zablokowana do: HH:MM" text.
- **Only the User changes it (FR-20).** The admin form has no Account type control.
- **After a successful save only (FR-21, WF-02):**
  - announce "Account type changed" as a domain event other modules can react to (`module-events`);
  - add a Journal entry of kind `users` for each member of the "Admins" group, using the Journal from ticket 05;
  - record the change for audit. Audit's listener comes with the Audit rework, so emitting the event is enough here.

## Done when

- WF-02 is tested: the happy path both ways; validation failure produces no lock, no event and no entry; a locked toggle is refused; Company without the declaration is refused.
- The event is dispatched only after the save, and this is tested.
- `composer check` passes.

## Comments

### Worker — 2026-11-01
- **Built:** `User` entity owns RULE-04/05/07 (`changeAccountType`: Company needs NIP + address + accepted declaration, records date and version "V1"; Private clears; 30-minute lock; Company without recorded acceptance reads as Private; a Company keeps NIP and address). `UpdateUserCommand` gained `accountType` + `sellerDeclarationAccepted`; the handler refuses anyone but the User themself (FR-20, system included), validates before saving, and dispatches the new domain event `AccountTypeChanged(userId, accountType)` only after `save` (FR-21, WF-02). Listener `WriteAccountTypeChangeJournalEntries` (after commit, failures reported not thrown) writes a `users` Journal entry per `AdminRecipients::userIds()` via `JournalWriter`; `users` subject resolver registered (`UserJournalSubject` + `UserUrls` port). Migration adds declaration date/version and `account_type_changed_at` (backfills migrated Company Users with now/"V1"). "Mój profil": Account type radio (locked with "Zmiana rodzaju konta jest zablokowana do: HH:MM"), declaration checkbox, VAT-only private notice, "Pamiętaj!" modal with "Rozumiem", required phone, "i" navigation badge while not Company, 20-character password generator filling both fields, "Zapisz" / "Zapisano dane profilu", domain refusals shown to the User. Admin form has no Account type control (tested). `changePassword` still calls `KeepsOwnSessionAfterPasswordChange`.
- **Deviations:** Account type travels in `UpdateUserCommand` rather than a separate command, so profile data and type save atomically (WF-02 "nothing saved on validation failure"). Audit record is the existing `RecordChanged` observer path / the event; no audit listener (comes with Audit rework). Password change stays on its own tab/command (existing design) instead of inside the single "Zapisz" form.
- **Seams left:** Offers/WooCommerce/Otomoto listen to `AccountTypeChanged`; Audit listener; Deactivated filtering in `AdminRecipients` (ticket 07).
- **Open questions:** none
- **Check:** composer check green

### Worker — fix 1
- **Built:** The Company-data invariant (RULE-04) now holds on the resulting state: `updateData()` no longer checks it; the handler applies data and Account type, then calls the new `User::assertConsistent()` before saving. Company to Private with NIP/address cleared in one save succeeds (declaration cleared, event dispatched); a Company staying Company cannot clear NIP (nothing saved). Tests added.
- **Deviations:** none
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-01
- **Verdict:** approved after 1 fix round
- **Notes:** Round 1 fixed the Company-data check running against the pre-change Account type; the handler now calls `User::assertConsistent()` on the final state, so any new handler that changes User data must call it too. Accepted: the Account type travels in `UpdateUserCommand` (atomic profile save, WF-02); the password change stays its own form/command. `AccountTypeChanged` is dispatched after the save; Admins' `users` Journal entries are written by an after-commit listener. No Audit listener yet (Audit rework). Migration backfills existing Company Users with acceptance now/"V1", as ticket 10 specifies.
