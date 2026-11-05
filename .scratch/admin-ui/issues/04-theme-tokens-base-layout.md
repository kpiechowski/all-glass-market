# 04 - Theme: rebuild, tokens, base, layout and navigation

Status: ready-for-agent
Blocked by: 03

Spec: D-25..D-32, D-37.

## What

- Rebuild `resources/css/admin/` to the D-27 tree. Remove the old files instead of carrying them over; keep the primary scale `#003eb2`.
- `tokens/`: neutral grey scale, primary, semantic colours with soft tints for badges, typography scale (base 14 px), spacing, radii, borders, shadows.
- `base/` and `filament/layout/`: sidebar, topbar, page header, breadcrumbs, content width.
- Navigation (D-25): `->navigation()` in `AdminPanelProvider` with an explicit top group and a bottom group (profile, later settings). Both stretch over the full sidebar and the bottom group is aligned to the bottom. Every existing resource and page stays reachable.
- The user picks the font on the Styleguide, the winner goes into `->font()`, and the switcher is removed.

## Done when

- The user has accepted the layout and the font on screenshots (D-37). Note each review round under Comments.
- `npm run build` and `composer check` pass.
