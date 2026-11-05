# Audit — delivery log

- **Branch:** `feat/audit` (spec + issues @ 6e754e1)
- **Scope:** issues 01–11, one worker at a time (user's rule). Another agent works on skills/MD files on this branch — never touch their uncommitted files.
- **Order:** 01 → 02 → 03 → 04 → {05, 06} ; 07 after 02 ; 08 → {09, 10} → 11

| Issue | Status | Worktree | Round | Last verdict |
|---|---|---|---|---|
| 01 core-recorded-changes | resolved (c7a84da) | removed | 1 | approved |
| 02 change-history-recording-and-registration | resolved (8f5657e) | removed | 1 | approved |
| 03 users-auditable | resolved (74d14b4) | removed | 1 | approved |
| 04 record-history-section | resolved (a834e40) | removed | 0 | approved |
| 05 central-audit-page | resolved (fafc4bc) | removed | 0 | approved |
| 06 restore | resolved (695c4dc) | removed | 0 | approved |
| 07 retention-cleanup | resolved (5bd7b94) | removed | 0 | approved |
| 08 technical-log-channels | resolved | removed | 1 | approved |
| 09 security-events | in progress | .worktrees/audit-09 | 0 | — |

## Next action

Review issue 09 when its worker returns. User: set `LOG_STACK=aplikacja` in local `.env`. Note: `ListUsersTest` flaked once during 04 review (passed on rerun). Migrations so far: Audit `2026_11_05_000000_create_audit_changes_table`, `2026_11_05_100000_add_restore_marker_to_audit_changes_table`.
