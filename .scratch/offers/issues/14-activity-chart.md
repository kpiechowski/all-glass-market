# 14 - Activity chart

Status: resolved
Blocked by: 10

Spec: FR-36, FR-37, §13 ADD-05. Decisions: D-58. Skills: `admin-ui-design`.

## What

- One line chart, last 12 months, two lines: "Dodane do sprzedaży" (Offers first approved that month) and "Sprzedane" (Sales that month, returned ones excluded); no money; Trash excluded.
- Seller: own Offers, on "Moje oferty" under the stat cards, collapsible. Admin: all Offers above "Wszystkie oferty", filterable by seller.
- Zeros when there is no history.

## Done when

- Monthly counts tested (a returned Sale, a trashed Offer, a month with nothing); `composer check` passes.

## Comments

### Worker — 2026-11-21
- **Built:** FR-36: `OfferQueries::monthlyActivity()` (two grouped reads, 12 months with zeros, window = first of the month 11 months back to end of the current month) and `sellersWithOffers()`. FR-37: `OfferActivityChart` widget (own SVG + `activity-chart.css`, legend, hover readout, hidden table view, Polish month labels in `widgets.php`), on "Moje oferty" under the stat cards for a seller, alone above the admin's list with a seller filter. Tests: queries (returned Sale, trashed Offer and its Sale, empty month, window edges, seller scoping) and the widget through Livewire (a seller never sees others' numbers, even with a tampered `sellerId`).
- **Deviations:** Trash reading: an Offer in the Trash counts in neither line, and a Sale of a trashed Offer is excluded too. Own SVG instead of Filament's chart widget (Chart.js is not installed; dataviz asks for the legend/hover/table-view set). Collapsible for the admin too (one shared view). Series colours: accent and `--warning-500` tokens (palette validator not run; panel has `darkMode(false)`, so no dark values exist). Month grouping uses `strftime` on SQLite and `DATE_FORMAT` elsewhere.
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check green (1694 tests); the diff touches resources/css/admin. The first run failed 4 Styleguide tests only because the worktree had no `public/build`; `npm run build` fixed it.

### Review — 2026-11-21
- **Verdict:** approved after 0 fix rounds. The manager made one change inline: the chart's seller filter says "Sprzedający", as the list filter from 10 does. The widget tests were re-run and are green.
- **Notes:**
  - **Counts:** `OfferQueries::monthlyActivity` uses two grouped reads and fills empty months with zeros. Trashed Offers and their Sales count in neither line.
  - **Chart:** own SVG, because Chart.js is not installed. It is collapsible for both roles. Sellers get their own numbers only, and a tampered `sellerId` is tested.
  - **Check:** the full `composer check` ran in the worktree, on top of 10–13: green, 1694 tests.

### Worker — fix (Filament chart)
- **Built:** `OfferActivityChart` rebuilt on Filament's `ChartWidget` (`getType()` = `line`, `$isCollapsible`, `getFilters()`/`$filter`): two datasets "Dodane do sprzedaży" and "Sprzedane" over 12 Polish month labels from `offers::widgets.activity_chart.*`, integer y-axis (`beginAtZero`, `ticks.precision` 0), series colours from Filament's `primary` and `warning` shades. A seller gets no filter and always own numbers; an admin's `$filter` (`all` or a seller id) is checked against `sellersWithOffers()`. Deleted the SVG view, `activity-chart.css` and its import, and unused translation keys (`toggle`, `seller_filter`, `table_caption`, `month_column`). `OfferQueries` and its tests untouched. Widget test adapted to dataset/label data; all four behaviours kept.
- **Deviations:** the filter's "all" entry is the string key `all` (Filament filter keys are strings); a tampered id that is no seller with Offers falls back to all for an admin. Dataset colours use `FilamentColor::getColor(...)[500]` (the theme's palette) rather than the old `--warning-500`/accent tokens.
- **Unmapped:** none
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check:module offers green (611 tests); tests/Feature/Filament (Styleguide, AdminPanelProvider) green after `npm run build`.

### Review — fix (Filament chart) — 2026-11-21
- **Verdict:** approved. The chart is now Filament's `ChartWidget` (line, collapsible, `getFilters()` for the admin's seller filter). The own SVG view and `activity-chart.css` are gone.
- **Manager's inline change:** in this panel `primary` and `warning` are the same amber, so both lines had one colour. "Sprzedane" now uses `info`, and a test checks that the two colours differ.
- **Check:** `composer check:module offers` green, 612 tests. The full check runs in the main tree as the milestone.
