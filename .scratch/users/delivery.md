# Users — delivery log

- **Branch:** `feat/users-01-09` (from `refactor/user-module` @ dfbf35f)
- **Scope:** issues 01–05; then 06–09. Issue 10 moved to `.scratch/data-migration/users/ticket.md` (scheduled later by the user).
- **Order:** 01 → 02 → {03, 05} → 04; then {06, 07, 08} after 03, 09 after 04

| Issue | Status | Worktree | Round | Last verdict |
|---|---|---|---|---|
| 01 roles-and-permissions | resolved (f90c970) | removed | 0 | approved |
| 02 user-management | resolved | removed | 0 | approved |
| 03 sign-in-and-panel-access | resolved | removed | 0 | approved |
| 04 profile-and-account-type | resolved | removed | 1 | approved |
| 06 passwords-invitations-forced-change | resolved | removed | 1 | approved |
| 07 deactivation | resolved | removed | 0 | approved |
| 08 extra-permissions-and-impersonation | resolved | removed | 0 | approved |
| 09 dashboard-account-tile | resolved | removed | 1 | approved |
| 11 qa-fixes | resolved | removed | 1 | approved |
| 12 polish-translations | resolved (haiku) | removed | 1 | approved |
| 13 super-admin-read-only-and-row-actions | resolved | removed | 0 | approved |
| 05 journal | resolved | removed | 0 | approved |

## Next action

Closed 2026-11-02: issues 01–09 and 11–13 resolved, panel checked by hand by the user. Open outside this run:
- V1 data import: `.scratch/data-migration/users/ticket.md`.
- Audit wiring (change history, Technical log): Audit module.
- Pending decisions D-45 (ADD-02 onboarding, ADD-03 2FA, ADD-04 data erasure).
- Minor QA notes: no success toast on set/clear "wymaga zmiany hasła"; reading an entry does not mark its bell notification read; declaration date on the user detail shows UTC; the RULE-04 message also lands on NIP when only the address is empty.
