# 08 - Tables: table, filters, pagination, empty state, mobile card

Status: resolved
Blocked by: 07

Spec: D-12..D-15, D-21, D-27, D-29, D-37.

## What

- `filament/tables/`: table without the heavy row lines and frame-in-frame look, header, row hover, description cell, inline filters, the filter panel, pagination, empty state. The active-filter badge shows only above 0.
- The mobile card: a reusable `ViewColumn` "card" in Core (`components/` CSS and a Blade view with title, subtitle, badges and meta lines) plus the theme CSS that hides the header and lines below `md`. No vendor template override (D-30).
- Document in the template A file of the skill how a list table declares its card.
- A composite "person" style cell (name, email, indicator) if `->description()` is not enough. Decide on the Styleguide.

## Done when

- The user has accepted the desktop and mobile table on screenshots.
- `npm run build` and `composer check` pass.

## Comments

- 2026-11-05 (user review of 04): inline filters above the table lay out inconsistently, sometimes on one line and sometimes full width. Filters in the dropdown panel are cramped. Fix both here.
- 2026-11-06 (agent): round 1. CSS on `fi-ta-*` (no vendor view), three Core table pieces, the Styleguide "Tabele i filtry" tab rebuilt.
  - New `tokens/tables.css`: every table value lives there.
  - `filament/tables/table.css`: one white surface with the section edge (`--ag-surface-edge`), no inner rings. Head white, labels 13 px medium gray-600 (the 07 entry label), 38 px, one gray-200 hairline below. Sortable labels: a 14 px chevron in gray-300, gray-500 on hover, royal when sorted (label goes near-black). Rows: one line 50 px (15 + 20 + 15), two lines (name + e-mail) 60 px; gray-100 hairlines between rows. Hover on a clickable row: primary-50 at 70 % on white, and the title cell (person name or a `->description()` cell's main line) turns royal, both on `--ag-duration-fast`. Selected row primary-50 plus Filament's royal bar. Cells 14 px medium near-black, descriptions 13 px muted 2 px below, tabular figures (already in base). Row actions: lone icon and "⋮" both 32 px, muted grey, royal on a primary-50 square on hover; the actions cell is padded so the row stays 50 px. Keyboard focus ring inside the focused cell.
  - `filament/tables/toolbar.css`: heading, inline filters and toolbar share one block, no hairlines between them (only the one above the head); 16 px rhythm, 24 px edge (16 px below sm). Heading 16 px bold navy like a section. Search keeps the 06 field (36 px), 18 rem from sm, full width below. Selection line primary-50 with a primary-100 top edge. Column manager: 20 px padding, 12 px between items, grey "Zaznacz wszystkie" link.
  - `filament/tables/filters.css`: inline filters are one wrapping row: selects/dates 13 rem (208 px) each, a toggle/checkbox its own width on the 36 px line, "Zresetuj filtry" (grey link) right after the last filter, full width per filter below sm; "Filtry" heading for screen readers only. The cause of the old inconsistency: Filament's viewport grid (2/3/4/5 columns by breakpoint). Panel: 24 rem, header strip (heading + reset) over a hairline, 20 px padding, 16 px between fields, "Zastosuj filtry" in a gray-50 footer strip on the right. The count badge is hidden at 0. Active-filter chips: one line under the toolbar, muted 13 px "Aktywne filtry", Filament's soft primary chips, no grey band.
  - `filament/tables/pagination.css`: 13 px muted overview, per-page select in the 06 frame, pages as separate 32 px squares (hover gray-100, current primary-50 + royal semibold), no outer frame, gray-200 rule above.
  - `filament/tables/empty-state.css`: 48 px primary-50 circle with a 24 px royal icon, heading 16 px semibold, one sentence 14 px muted (max 26 rem), action 20 px below.
  - `filament/tables/mobile.css` (D-12): below md (48 rem), for a table with a `RecordCardColumn` only: head hidden (also when empty), the table surface steps back so heading/filters/toolbar/pagination sit on the canvas, each row is a white card with the section edge, 8 px apart, no row lines; checkbox left on the title line, row actions top right; a tap tints the card, a selected card gets a royal edge.
  - Core PHP (`app/Core/UserInterface/Filament/Tables/`): `TableDefaults` (via `Table::configureUsing()` in `AdminPanelProvider::boot()`, moved there from CoreServiceProvider at the user's request): inline filters apply on change (no Apply button), a one-column filter panel is `Width::Small` (24 rem; the vendor `max-w-xs!` cannot be beaten from CSS), `data-active-filters` on the trigger (Filament renders a "0" badge otherwise), reset link only while a filter is active. `TableEmptyState::configure()`: own icon/sentence/primary action, and under a search or filter a magnifier, "Brak wyników dla tych filtrów", "Zmień wyszukiwanie albo wyczyść filtry." and grey "Wyczyść filtry" (core `tables.php` translations). `Columns\RecordCardColumn` (the mobile card: title, subtitle, status, badges, flags, meta; enums or arrays) and `Columns\PersonColumn` (initials, name, text flags, e-mail), with views in `core::filament.tables.*` and CSS in `components/list-card.css` (generalised), `person-cell.css`, `record-flag.css`. The old Styleguide `user-card` view is gone.
  - Styleguide "Tabele i filtry": list tabs, search, 3 inline filters, "Filtry w panelu" (5 filters incl. a date and a ternary), chips, sortable Użytkownik / Zamówienia / Data dołączenia, a numeric right-aligned "Zamówienia" column, hidden "Ostatnia zmiana" in the column menu, the person cell or `->description()` ("Komórka: opis / osoba"), row click, lone key action + "⋮", bulk actions, pagination 5/10/25, "Pokaż pusty stan" and "Pokaż brak wyników", the mobile card.
  - Recommendation: `PersonColumn` for lists of people (the indicator is readable text, no hover needed, works on touch); `->description()` for every other title + secondary line. Written into template A with the card rules.
  - pint, `npm run build` and `composer check` pass (647 tests).
- 2026-11-06 (manager): `Table::configureUsing(TableDefaults::apply(...))` moved from CoreServiceProvider to `AdminPanelProvider::boot()` at the user's request; rule added to `filament-module-wiring` (D-45).
- 2026-11-06 (manager): closed. The user accepted round 1 ("wygląda to dobrze"), including the panel-wide table defaults (inline filters apply on change, 24 rem panel, no "0" badge, reset only while filtered) and `PersonColumn` for lists of people. Open: filter chips also show with inline filters (slightly redundant); hide them only if the user asks.
