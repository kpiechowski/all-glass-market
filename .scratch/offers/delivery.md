# Offers — delivery log

- **Branch:** `catalog` (from 2e1c686)
- **Scope (user, 2026-11-20):** catalogue issue 09 first (blocks Offers 01 and 03), then Offers issues 01–14.
- **Order:** catalogue 09 → 01 → 02 → 03 → 04 → 05 → 06 → 07 → 08 → 09 → 10 → 11 → 12 → 13 → 14
- **Concurrency (user, 2026-11-11):** one ticket and one subagent at a time.
- **Fix rounds:** every fix round goes to a **fresh** `module-worker`, briefed with the issue path, its Comments log and the numbered findings.

| Issue | Status | Worktree | Round | Last verdict |
|---|---|---|---|---|
| catalogue 09 glass-model-picker | resolved (48282f2) | removed | 0 | approved |
| 01 offer-core | resolved (5d68513) | removed | 1 | approved |
| 02 photos | resolved (8ea14a9) | removed | 0 | approved |
| 03 approval | resolved (4db8d7f) | removed | 0 | approved |
| 04 history-and-changes-since-approval | resolved (abdb77c) | removed | 1 | approved |
| 05 prices-and-vat | resolved (16ce585) | removed | 0 | approved |
| 06 withdraw-and-trash | resolved (959f1e0) | removed | 1 | approved |
| 07 reservations-and-sales | waiting | — | — | — |
| 08 inquiries | waiting | — | — | — |
| 09 duplicate-and-bulk-actions | waiting | — | — | — |
| 10 lists-tabs-filters-stat-cards | waiting | — | — | — |
| 11 catalogue-uses-and-offer-counts | waiting | — | — | — |
| 12 deactivated-seller | waiting | — | — | — |
| 13 channel-announcements-and-advert-summary | waiting | — | — | — |
| 14 activity-chart | waiting | — | — | — |

## Next action

2026-11-22: Offers 06 merged (959f1e0). Next: dispatch 07 (reservations-and-sales). Migrations so far: `create_offers_table` (01), `add_photos_to_offers_table` (02), `add_approval_dates_to_offers_table` (03), `create_offer_settings_table` (05), `add_withdrawal_and_trash_to_offers_table` (06) — run `dartisan migrate`; public photos need `dartisan storage:link` once.
