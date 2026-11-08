# 04 - Theme: rebuild, tokens, base, layout and navigation

Status: resolved
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

## Comments

- 2026-11-05 (manager, from 03): `theme.css` has no `@source` for `app/Core/UserInterface/resources/views/**/*`, so Tailwind utilities in Core Blade (Styleguide card, font switcher) do not compile; 03 used inline styles. Add the `@source` and move those inline styles to classes.
- 2026-11-05 (agent, round 1, awaiting user review):
  - Theme rebuilt to the D-27 tree; all old files removed (`_config.css`, `globals.css`, `layout/*`, `components/*` except the new `list-card.css`, old `pages/login.css` rewritten). `theme.css` holds imports and `@source` only and now sources `app/Core/UserInterface/resources/views/**/*` (dropped the two `@source` paths that no longer exist: `app/Filament`, `resources/views/filament`).
  - `tokens/`: `colors.css` overrides Filament's own `--gray-*`, `--primary-*`, `--success/warning/danger/info-*` on `:root` (our theme loads after `@filamentStyles`, so it wins), plus role aliases (`--ag-canvas`, `--ag-surface`, `--ag-text*`, `--ag-accent*`) and soft-tint triples `--ag-soft-{color}-{bg,text,border}` for badges. `typography.css` 12/13/14/16/18/22 px scale, base 14 px, no typeface named (everything reads `--font-family` via `--font-sans`). `spacing.css` 4 px steps + topbar height and page gutters. `radii.css` overrides Tailwind `--radius-lg` 6 px (controls) and `--radius-xl` 10 px (containers). `borders.css` 1 px gray-200. `shadows.css` overrides Tailwind `--shadow-xs..xl` to faint values.
  - `base/` canvas gray-50, text gray-900, 14 px body. `filament/layout/`: `page.css` (gutters, block gap; content max width stays Filament's 7xl), `topbar.css` (56 px, white, hairline, brand column as wide as the sidebar), `sidebar.css` (white with hairline edge, quiet grey items, active = primary-50 tint + primary text/icon, bottom block pushed down with `margin-top: auto` and a hairline above it), `page-header.css` (22 px semibold title, 14 px muted subheading), `breadcrumbs.css` (13 px muted).
  - Styleguide inline styles moved to classes: `components/list-card.css` (mobile card) and `pages/styleguide.css` (font switcher). Switcher still swaps `--font-family`; font not chosen, `->font('Poppins')` unchanged.
  - Navigation (D-25): `AdminPanelProvider::navigation()` builds two `NavigationGroup`s. Top: Dashboard, Users, Journal, Audit log, Technical logs, Styleguide (local only). Bottom (`sidebar-group--bottom`): EditProfile (user-menu item kept). Each item comes from the class's own `getNavigationItems()` (label, icon, badge, active state) and is `visible()` only when `shouldRegisterNavigation() && canAccess()`. `->sidebarWidth('16rem')` added.
  - Tests: new `tests/Feature/Providers/Filament/AdminPanelProviderTest.php` (super admin sees all, plain user sees only Dashboard/Journal + profile, badge kept, Styleguide absent outside local, sidebar renders); `StyleguideTest` gained a local check that the Styleguide is the last top item. `npm run build` OK, `composer check` OK (639 tests).
  - Not styled on purpose (issues 05–09): sections, forms, tables, buttons, badges, modals. The old checkbox-tick and badge overrides are gone, so those show Filament defaults on the new tokens.
- 2026-11-05 (agent, round 2, awaiting user review):
  - Font: `->font('Manrope')`. Switcher removed (Blade, `pages/styleguide.css`, the include, docblock line, `Krój pisma` assert). Page and login titles 700, buttons 600, nav 500 (active 600). `font-variant-numeric: tabular-nums` on `.fi-ta-cell`, `.fi-ta-text-item`, `.fi-in-text-item`, stats values, badges, pagination (`base/typography.css`).
  - Logo: `public/images/brand/allglass-logotype.svg` (copied as is), `->brandLogo(asset(...))` + `->brandLogoHeight('2rem')`; it shows on login/auth pages through Filament's simple layout (test added).
  - Brand (D-40): `--brand-navy` #001b4e, `--brand-royal` #003eb2, `--brand-sky` #0070c0. Primary 800 #00286f, 900 #00205c, 950 = navy. Navy used for page and login titles (`--ag-heading`), primary button hover/press, the focus outline (`--ag-focus-ring`, 2 px, offset 2 px), active nav label and a 3 px navy indicator bar on the active item. Sidebar background untouched (D-41 open).
  - Buttons (`filament/actions/button.css`, `icon-button.css`, `link.css`): heights xs 28 / sm 32 / md 36 (= input) / lg 40 / xl 44, padding-x 8/10/14/16/18, gap 4-8 px, radius 6 px, weight 600, 1 px border on all (transparent on filled). Primary royal → navy on hover; grey white + gray-300 border, gray-50 hover; danger filled only for confirm (danger-600 → 700); disabled 50 % + not-allowed; focus = navy outline. Icon buttons: 6 px radius, grey-100 hover square, gray-500 → gray-900 icon. Toggle buttons (labels / aria-pressed) keep Filament's checked fill. No chamfer (D-41).
  - `npm run build` OK, `composer check` OK (640 tests).
- 2026-11-05 (manager): user accepted. Font sits well. Colour is undecided: "the panel feels very white". The plan stays; restyled components in 05–09 may add accent. Revisit with D-41.
