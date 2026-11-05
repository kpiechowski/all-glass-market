# 03 - Users registers the User as auditable

Status: ready-for-agent
Blocked by: 02

Spec: FR-05, FR-07, FR-11, §9 Users. Users spec §9 "Audit" (amended 2026-11-02). Decisions: D-17, D-21, D-23, D-33.

## What

- Users' persistence emits `RecordChanged` for the User through an observer. The User model's adapter uses the Core relation-sync helper for anything relation-like it wants audited. Extra Permissions are a JSON list, so present them as a list field with added/removed values.
- Users registers the User type with Audit:
  - **Name:** "Użytkownik".
  - **Title:** the User's name.
  - **Link:** the user detail.
  - **Labels:** Polish.
  - **Excluded:**
    - password, remember token, any 2FA secrets;
    - invitation and reset tokens;
    - pure timestamps that are not business data.

    Sensitive fields are simply not registered (D-17).
  - **Audited:**
    - contact and company data;
    - Role;
    - Account type and declaration date;
    - deactivation state;
    - Extra Permissions;
    - the "password change required" marker (D-23).
  - **Presenters:** Role and Account type by label, Extra Permissions by their translated names.
  - **Restorable fields:** none for now.
- The registration is wired in Users' module provider. Nothing in Audit names Users.

## Done when

- Tests show that creating, editing, deactivating and reactivating a User, changing their Account type, granting or revoking an Extra Permission, and setting or clearing the marker each produce the right Change entry with the right Actor.
- A password change produces no field value.
- `composer check` passes.

## Comments
