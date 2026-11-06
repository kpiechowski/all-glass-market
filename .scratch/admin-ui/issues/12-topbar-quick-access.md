# 12 - Topbar: universal "back" action and the quick-access hub

Status: needs-triage
Blocked by: 11 (and real modules to link to)

Spec: D-44 (topbar ornament).

## What

These are the user's ideas from 2026-11-06, noted for later. Nothing has been designed yet.

- **Universal "back" ("Cofnij") action** on the topbar's left edge, on or right next to the glass-pane ornament (D-44). Open questions:
  - Is it browser history back, or "up one level" (a record goes to its list)?
  - What does it do on the dashboard, where there is nowhere to go back to?
  - Does it replace breadcrumbs or sit next to them?
- **Quick-access hub**, in line with the "back" action: a "4 squares" icon (grid / apps) that opens a hub of tiles.
  - Each tile has an icon and links to one of the most important places in the panel. The tiles are grouped, like the DirectAdmin dashboard.
  - It can be a full page (a Filament page) or a large dropdown or panel from the topbar. Decide when designing.
  - Open questions:
    - Where does the tile list come from? It could be the same explicit list as `AdminPanelProvider::navigation()`, a separate list of "most important" items, or something per role.
    - Visibility: only what the user can open (`canAccess`).
    - Does it overlap the sidebar's job? The hub is for orientation, the sidebar for everyday moves.

## Done when

- The user has agreed on the behaviour and the look (grill first, then the Styleguide).
- `npm run build` and `composer check` pass.

## Comments
