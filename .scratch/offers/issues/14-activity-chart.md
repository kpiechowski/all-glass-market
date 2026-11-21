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
