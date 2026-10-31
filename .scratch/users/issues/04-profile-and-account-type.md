# 04 - Own profile, Account type, Seller declaration, lock, announcement

Status: ready-for-agent
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
