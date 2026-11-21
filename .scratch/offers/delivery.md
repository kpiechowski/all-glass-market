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
| 07 reservations-and-sales | resolved (c5d27de) | removed | 0 | approved |
| 08 inquiries | resolved (e87d3f9) | removed | 0 | approved |
| 09 duplicate-and-bulk-actions | resolved (4fb5fa7) | removed | 0 | approved |
| 10 lists-tabs-filters-stat-cards | resolved (68ab0d0) | removed | 0 | approved |
| 11 catalogue-uses-and-offer-counts | resolved (40282c5) | removed | 1 | approved |
| 12 deactivated-seller | resolved (846e3d3) | removed | 0 | approved |
| 13 channel-announcements-and-advert-summary | resolved (3c1449a) | removed | 0 | approved |
| 14 activity-chart | resolved (d6c85bd, fix 0935d36) | removed | 0 | approved |
| 16 quality-audit-easy-fixes | resolved (9422fe1) | removed | 1 | approved |

## Next action

2026-11-21: single-issue run for 16 (quality-audit easy fixes, A–F). No business behaviour change; anything that would change it goes to the user as an open question. Out of scope list untouched. Closes with full `composer check` in the main tree; remind the user of `dartisan migrate`. 16 merged (9422fe1) after 1 fix round (migration backfill test). Milestone `composer check` in the main tree on `catalog` green (1773 tests). Migration added: `2026_11_21_200000_add_open_sale_guard_and_indexes` — run `dartisan migrate`. Waiting on the user to confirm the user-visible effects listed in 16's review.

2026-11-21: run 10–14 started (new manager, from handoff.md). 10 merged (68ab0d0; full check green, 1604 tests). 11 merged (40282c5, 1 fix round). 12 merged (846e3d3). 13 merged (3c1449a). 14 merged (d6c85bd); the chart was redone on Filament's ChartWidget (0935d36). All of 10–14 are resolved. The milestone `composer check` in the main tree on `catalog` is green (1695 tests). Run 10–14 added no migrations. Next: bonus issue 15 (`needs-info`), which needs the user's grilling first.

2026-11-22: Offers 09 merged (4fb5fa7). Milestone stop (user): the next manager starts from `.scratch/offers/handoff.md`, then dispatches 10. `composer check` has no time limit any more; `composer check:module offers` is the quick check while working. Migrations so far: `create_offers_table` (01), `add_photos_to_offers_table` (02), `add_approval_dates_to_offers_table` (03), `create_offer_settings_table` (05), `add_withdrawal_and_trash_to_offers_table` (06), `create_offer_reservations_and_sales_tables` (07), `create_offer_inquiries_table` (08) — run `dartisan migrate`; public photos need `dartisan storage:link` once.

## User decisions on the open questions (2026-11-21)

1. Where the Offer's events are dispatched (entity flag, repository dispatches): leave as is; it will come up in the module's technical audit.
2. and 5. A business history of the Offer (section or slide-over, rejection reason included): bonus issue 15, `needs-info`, grill the user first.
3. Sale date stays the click moment.
4. The picker keeps the "with withdrawn" toggle.
6. A VAT rate change is not audited.
7. and 8. The "N ofert" count by viewer and `OfferSold` alone on a sale stay as they are.
9. Issue 14's chart is own SVG because of a wrong claim ("Chart.js not installed"). Filament ships `ChartWidget` / `LineChartWidget` with Chart.js in its widget bundle (`public/js/filament/widgets/components/chart.js`). To be redone on Filament's chart widget; see the user's answer.
