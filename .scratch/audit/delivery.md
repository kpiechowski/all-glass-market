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
| 08 technical-log-channels | resolved (c12e92f) | removed | 1 | approved |
| 09 security-events | resolved (351176d) | removed | 1 | approved |
| 10 technical-log-page | resolved (40ae71d) | removed | 0 | approved |
| 11 failed-jobs-tab | resolved (f803405) | removed | 0 | approved |

## Next action

Closed 2026-11-05: issues 01–11 resolved. Open for the user:
- Run `dartisan migrate` (Audit: `2026_11_05_000000_create_audit_changes_table`, `2026_11_05_100000_add_restore_marker_to_audit_changes_table`).
- Set `LOG_STACK=aplikacja` in local `.env`.
- Check the panel by hand: Historia zmian on a user, Dziennik audytu, Logi techniczne + Nieudane zadania.
- Notes: `ListUsersTest` flaked once (Users); no module offers Restore yet; money presenter assumes zloty (Offers to confirm); failed-jobs paging is in memory.
