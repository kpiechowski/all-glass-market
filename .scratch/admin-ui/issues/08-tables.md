# 08 - Tables: table, filters, pagination, empty state, mobile card

Status: ready-for-agent
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
