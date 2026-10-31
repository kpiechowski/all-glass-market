# 10 - Migrate V1 Users data

Status: needs-triage
Blocked by: 01, 02, 03, 04, 05, 06, 07, 08, 09

Spec: §8 "Migration from V1". Decisions: D-38, D-40, D-47.

## What

A repeatable import of V1 accounts into V2, as an Artisan command.
- **Copied:** contact and company data, Otomoto shipping text, join date.
- **Role mapping:** V1 User → User, V1 Admin → Admin. Super Admins are granted afterwards by the console command from ticket 01.
- **Account type:** Company only when V1 had both the company flag and the declaration flag. The acceptance date is the migration date and the version "V1".
- **Journal:** V1 entries become kind `offer`, with HTML stripped to plain text.
- **Not migrated:** password hashes, 2FA data, tokens, sessions, profile photos, feed rows.
- **Passwords:** every migrated User receives an invitation. Without mail, the migration uses temporary passwords that set "password change required", and prints them for the Admin.

Status is `needs-triage`, because the source connection (V1 database access from the V2 environment) and the run procedure are not decided. Settle them with the user before an agent starts.

## Done when

- The command is idempotent per e-mail and tested against a fixture of V1 rows.
- `composer check` passes.

## Comments
