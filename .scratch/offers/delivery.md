# Offers — delivery log

- **Branch:** `catalog` (from 2e1c686)
- **Scope (user, 2026-11-20):** catalogue issue 09 first (blocks Offers 01 and 03), then Offers issues 01–14.
- **Order:** catalogue 09 → 01 → 02 → 03 → 04 → 05 → 06 → 07 → 08 → 09 → 10 → 11 → 12 → 13 → 14
- **Concurrency (user, 2026-11-11):** one ticket and one subagent at a time.
- **Fix rounds:** every fix round goes to a **fresh** `module-worker`, briefed with the issue path, its Comments log and the numbered findings.

| Issue | Status | Worktree | Round | Last verdict |
|---|---|---|---|---|
| catalogue 09 glass-model-picker | resolved (48282f2) | removed | 0 | approved |
| 01 offer-core | in progress | .worktrees/offers-01 | 0 | — |
| 02 photos | waiting | — | — | — |
| 03 approval | waiting | — | — | — |
| 04 history-and-changes-since-approval | waiting | — | — | — |
| 05 prices-and-vat | waiting | — | — | — |
| 06 withdraw-and-trash | waiting | — | — | — |
| 07 reservations-and-sales | waiting | — | — | — |
| 08 inquiries | waiting | — | — | — |
| 09 duplicate-and-bulk-actions | waiting | — | — | — |
| 10 lists-tabs-filters-stat-cards | waiting | — | — | — |
| 11 catalogue-uses-and-offer-counts | waiting | — | — | — |
| 12 deactivated-seller | waiting | — | — | — |
| 13 channel-announcements-and-advert-summary | waiting | — | — | — |
| 14 activity-chart | waiting | — | — | — |

## Next action

2026-11-20: catalogue 09 merged (48282f2; no migrations, `npm run build` done). Open for the user: admin picker keeps the "with withdrawn" toggle; hand-check the slide-over picker. Offers 01 dispatched to a worker in `.worktrees/offers-01`.
