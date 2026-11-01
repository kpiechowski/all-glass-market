# Users — migrate V1 data

Status: needs-triage
Blocked by: Users issues 01–09 (resolved on `feat/users-01-09`)

Spec: `.scratch/users/functional-spec.md` §8 "Migration from V1". Decisions: D-38, D-40, D-47. Discovery: `.scratch/users/discovery/discovery.md` (V1 anchors and vocabulary). Moved here from `.scratch/users/issues/10-v1-data-migration.md`; there is no `spec.md` yet, because Users' discovery predates Phase 9.

## What

A repeatable import of V1 accounts into V2, as an Artisan command.
- **Copied:** name, e-mail, phone, company data, pickup address, city, postal code, Otomoto shipping text, join date.
- **Role mapping:** V1 User → User, V1 Admin → Admin. Super Admins are granted afterwards with `users:grant-super-admin`.
- **Account type:** Company only when V1 had both the company flag and the declaration flag. The acceptance date is the migration date and the version "V1".
- **Journal:** V1 entries become kind `offer`, with HTML stripped to plain text.
- **Not migrated:** password hashes, 2FA data, API tokens, sessions, remember tokens, profile photos, the "Aktywność użytkowników" feed rows.
- **Passwords:** every migrated User receives an invitation. Without mail, the migration uses temporary passwords that set "password change required", and prints them for the Admin.

## Data notes

What the Users build settled since the ticket was written:

- **V1 sources:** `users` (`company_account`, `has_accepted_terms`, `company_*`, `shipment_address`, `city_code`, `shipping_information`), `user_journals`. V1 Roles come from the Shield/Spatie role tables (`hasRole`, `super_admin`, `panel_user`), not from a `users` column, so the Role mapping reads those tables. Confirm the V1 Role names before building the mapping.
- **V2 information with no V1 source**, and its start value: not Deactivated; "password change required" set only on the temporary-password path; no Extra Permissions; no Account-type lock.
- **V2 Journal** is `journal_entries`, owned by Users, and is written through `JournalWriter`.

Status is `needs-triage`, because the source connection (V1 database access from the V2 environment) and the run procedure are not decided. Settle them with the user before an agent starts.

## Done when

- The command is idempotent per e-mail and tested against a fixture of V1 rows.
- `composer check` passes.

## Comments
