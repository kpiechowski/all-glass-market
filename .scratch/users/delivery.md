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
| 11 qa-fixes | in progress | .worktrees/users-11 | 0 | — |
| 12 polish-translations | resolved (haiku) | removed | 1 | approved |
| 05 journal | resolved | removed | 0 | approved |

## Next action

Scope 01–09 done. QA round: 11 and 12 running; review, merge, then a short QA re-run: only the fixes from 11 and 12, plus each UI action that sends a command, once. Everything else is covered by tests and composer check. The V1 data import waits in `.scratch/data-migration/users/ticket.md`.
