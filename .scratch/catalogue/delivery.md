# Catalogue — delivery log

- **Branch:** `catalog` (from 1579775)
- **Scope:** issues 01–08. Issue 09 waits for Offers; the data-migration ticket is scheduled separately.
- **Order:** 01 → {02, 03} → {04, 05} → 06 → 07 → 08
- **Concurrency (user, 2026-11-11):** one ticket and one subagent at a time, from the tickets after 02 and 03 (which already run in parallel).
- **Fix rounds:** every fix round goes to a **fresh** `module-worker` (user's rule for big tickets), briefed with the issue path, its Comments log and the numbered findings.
- **Never commit:** `zweryfikwoania_klientem.md` (user's file in the main tree).

| Issue | Status | Worktree | Round | Last verdict |
|---|---|---|---|---|
| 01 module-and-dictionaries | resolved (a4592b6) | removed | 0 | approved |
| 02 vehicles | resolved (f2f2a3a) | removed | 0 | approved |
| 03 glass-models | resolved (9b094d9) | removed | 0 | approved (manager merged with 02) |
| 04 eurocodes-of-a-glass-model | resolved (c846d57) | removed | 0 | approved |
| 05 fitment | resolved (ae9c9a9) | removed | 0 | approved |
| 06 glass-model-cards-and-search | resolved (7281459) | removed | 0 | approved |
| 07 thumbnail | resolved (2a18c4a) | removed | 0 | approved |
| 08 module-api-for-offers | resolved (27c73a3) | removed | 1 | approved |
| 10 seeding-testowy-katalogu | resolved (c4aeda2) | removed | 0 | approved |
| 11 glass-model-list-as-catalogue-cards | fix round 1 merged (3bc9bbd), waits for user screen review | `.worktrees/catalogue-11` (kept for round 2) | 1 | technical: approved |

## Next action

2026-11-14: issue 11 reopened for one fix round from the user's screen review (target layout in the issue Comments). Fresh worker in `.worktrees/catalogue-11`; after technical review merge again so the user can look.
Sheet double-hairline fix merged (607f300, `entry.css`). Both wait for the user's screen review; round 2 (if any) goes to a fresh worker in the kept worktree; issue 11 is resolved on acceptance.

Closed 2026-11-12: issue 11 resolved and merged (af59b66), `npm run build` done in the main tree. No migrations. The user checks `/admin/glass-models` at desktop/390px, light/dark; visual feedback becomes follow-up rounds. Open: Core Authorizer runs one `users` role query per row action (cache per request?).

Closed 2026-11-12: issue 10 resolved and merged (c4aeda2). No migrations. To check the panel by hand: `dartisan seed-katalog` (default `m`; `s`/`l`; `--force` skips the question).


Closed 2026-11-11: issues 01–08 resolved and merged into `catalog`. Before the user checks the panel by hand: `dartisan migrate` (01 `create_catalogue_dictionary_values_table`, 02 `create_catalogue_vehicles_table`, 03 `create_catalogue_glass_models_tables`, 05 `create_catalogue_glass_model_vehicles_table`, 07 `create_catalogue_thumbnail_mappings_table`) and `npm run build` (glass-card.css). Open outside this run:
- 09 Offers-facing pieces (waits for the Offers module).
- Catalogue data migration ticket.
- Worth a look in the panel: thumbnail artwork and clear-glass default (07); admin list search capped at 20 models (06).
