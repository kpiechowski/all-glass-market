# 05 - Schema components: section, tabs, fieldset, grid

Status: resolved
Blocked by: 04

Spec: D-08, D-11, D-31, D-27, D-29, D-37.

## What

- Style the native components in `resources/css/admin/filament/schema/`, one file per component, on the tokens from 04.
- Base look only. Variants come in issue 10.
- Work against the Styleguide. Report what changed per file so the user can review.

## Done when

- The user has accepted the look on screenshots. Note each round under Comments.
- `npm run build` and `composer check` pass.

## Comments

- 2026-11-05 (manager): user feedback after 04: "the panel feels very white". Within the quiet direction, give sections and tabs more presence: canvas vs surface contrast, a clearer section header, navy for the active tab. Do not add decoration (D-41 is open).
- 2026-11-05 (agent): round 1, base look of section, tabs, fieldset, grid.
  - "Too white": `--ag-canvas` goes from gray-50 to gray-100 (`tokens/colors.css`), so white surfaces stand off the page. New token `--ag-surface-edge` (`tokens/shadows.css`): 1 px gray-200 ring + hairline `xs` shadow, shared by sections and contained tabs. No header band, no accent bar (D-41 open).
  - `filament/schema/section.css`: white surface, radius 10 px. Header 16/24 px, navy bold 16 px title, muted 13 px description, actions centred on the right, one divider above content; content 24 px; footer 12/24 px with a divider. Mobile (<md): 12/16, 16, 12/16. Nested and compact sections: 10/16, 16 px, title 14 px semibold dark grey, ring only (no lift). Collapsible: hovering the header darkens the chevron and turns the title royal; the chevron rotates (off under reduced motion). Every measure is a `--section-*` variable for the issue 10 variants.
  - `filament/schema/tabs.css`: underlined everywhere. One baseline (gray-300 on the canvas for page/list tabs, gray-200 inside a contained card), active tab = 2 px navy bar on the baseline + navy label; hover = grey bar. No pills, no item backgrounds, all labels semibold (no width jump). Page tabs flush left, no box. Contained tabs: section surface, bar and panel inset 24 px (16 px mobile). Mobile scrolls sideways, thin scrollbar (hidden on touch). Vertical tabs mark the end edge.
  - `filament/schema/fieldset.css`: gray-200 frame, radius 10 px, padding 20 px (16 px mobile), legend 14 px semibold.
  - `filament/schema/grid.css`: schema gap = section padding: 24 px, 16 px on mobile (dense keeps 12 px).
  - Styleguide: new section "Punkty odbioru" in the form (footer action, nested section "Magazyn Poznań", fieldset "Godziny otwarcia").
  - `npm run build` and `composer check` pass (640 tests).
- 2026-11-05 (user, via manager): round 1 review: tabs, buttons and section headers are good. The gray-100 canvas reads dirty and too dark. The real cause of "too white" is that there is no dark mass anywhere. Trial a navy sidebar (D-41) and make the Styleguide block headings stronger.
- 2026-11-05 (agent): round 2.
  - Canvas: `--ag-canvas: #f3f6fb`, a very light cool blue in the navy's hue (contrast against white 1.08, gray-100 was 1.13). Cards keep their ring edge.
  - Navy sidebar trial (D-41): new `tokens/sidebar.css` holds every sidebar colour in two `:root` blocks, the light sidebar and then the navy trial. `filament/layout/sidebar.css` now reads only `--ag-sidebar-*`. **To revert, delete the second block in `tokens/sidebar.css`.** Colours: background navy #001b4e with no edge; labels white 78 % (10.4:1); icons white 60 % (6.6:1); group labels white 58 % (6.2:1); hover a white 8 % overlay with white text; active item a white 12 % fill with white semibold text (11.8:1), and its icon and 3 px bar in the new `--brand-sky-light` #5aa9e6 (6.5:1 on navy); dividers white 12 %; focus outline sky-light; mobile overlay navy at 55 %. The topbar and the mobile drawer header (logo + close button) stay white, so the logo always sits on white. Sidebar badges are untouched: they are opaque light chips with dark text, so they stay legible on navy (issue 09 restyles badges).
  - Styleguide block headings: an `h2` + description in one `Html` block (`Styleguide::blockHeading()`), styled in the new `pages/styleguide.css`. Title 18 px bold near-black, description 14 px grey, 16 px of extra space above.
  - `npm run build` and `composer check` pass (640 tests).
- 2026-11-05 (user, via manager): round 2 review: the navy direction is good, but the navy must run to the top of the window. The logo moves into the navy sidebar header and uses the client's official white logo (`public/images/brand/allglass-logotype-white.svg`).
- 2026-11-05 (agent): round 3. CSS only: no vendor view override, the topbar stays on.
  - The whole trial now lives in `filament/layout/sidebar-navy.css`: the navy tokens (moved out of `tokens/sidebar.css`, which now holds only the light sidebar), the header and logo swap, and the desktop layout. **To revert, delete the one `@import "./filament/layout/sidebar-navy.css";` line in `theme.css`.**
  - Desktop (lg+), under `.fi-body-has-topbar:not(.fi-body-has-top-navigation)`:
    - `.fi-sidebar` is `top: 0; height: 100dvh; margin-top: calc(-1 * var(--topbar-height))`, still sticky.
    - `.fi-topbar-ctn` gets `margin-inline-start: var(--sidebar-width)`.
    - `.fi-topbar-start` (the logo) is hidden.
    - `.fi-sidebar-header` is shown at the topbar height (vendor hides it at lg), with the logo aligned to the item icons. Its white 12 % divider is level with the topbar's bottom border.
    - Collapsible sidebars are not enabled today. If they are turned on, the topbar follows the collapsed width through `:has(.fi-main-sidebar:not(.fi-sidebar-open))`, and only the logo link is hidden in `.fi-topbar-start`.
  - Mobile: the white topbar is unchanged. Below lg, Filament shows no logo in the topbar, as before. The drawer is navy including its header, with the white logo and light icon buttons.
  - Logo: `AdminPanelProvider::brandLogo()` returns an `HtmlString` with two `<img>` tags inside Filament's `.fi-logo` box (height 2rem): `brand-logo--on-light` (the dark file) and `brand-logo--on-dark` (the white file). Both carry the same alt text. `components/brand-logo.css` shows the dark one by default (login, auth pages, topbar) at 100 % of the box height, so the 151x44 and 151x43 files share a baseline. The trial shows the white one in the sidebar header. `allglass-logotype-light.svg` was never created. The logo test now asserts both images and classes.
  - pint, `npm run build` and `composer check` pass (640 tests).
- 2026-11-06 (manager, handoff): round 3 is committed as WIP and the issue is still open. The user finds the full-height navy sidebar with the white logo "interesting; we can still make something of it", to continue the next day. Open: refine the navy sidebar (D-41), then accept 05. Next: 06 forms.
- 2026-11-06 (user, via manager): round 3 review: keep the navy sidebar and make it more readable and pleasant (D-41, D-42). Add a top block holding only the dashboard (D-25a). Restructure the Styleguide into page tabs (D-43). Round 3 is committed as WIP 9bc82d8.
- 2026-11-06 (agent): round 4.
  - New `tokens/motion.css` (D-42): `--ag-duration-fast` 150 ms, `--ag-duration-base` 200 ms, `--ag-ease-out` cubic-bezier(0.22, 1, 0.36, 1). All of them are 0 under `prefers-reduced-motion`.
  - Navy sidebar, all in `filament/layout/sidebar-navy.css` (revert: delete its import line):
    - Items 42 px tall, 4 px apart; 20 px between groups.
    - Labels white 88 % (13:1), icons 72 %, group labels 60 %.
    - Hover: a white 7 % overlay; active: a white 13 % fill with a sky-light bar and icon.
    - On hover the label slides 4 px from its icon (base duration, ease-out) while the colours fade (fast duration). The active item keeps the slide.
    - Badges: a soft chip, white 14 % background with white semibold text (11:1, 7.5:1 on the active item).
  - D-25a: `AdminPanelProvider::navigation()` now builds three groups:
    - top: Dashboard only, `.sidebar-group--top`, closed by a divider in `sidebar.css`;
    - main: Users, Journal, Audit log, Technical logs, Styleguide, right under it;
    - bottom: the profile, still pushed down.
    - Visibility checks are unchanged. The provider and Styleguide tests now expect 3 blocks.
  - D-43: the Styleguide content is one uncontained schema `Tabs` with `->persistTabInQueryString('obszar')`. Every panel stays rendered, so switching keeps form state. Tabs:
    - Formularze: the form with its sticky footer;
    - Sekcje i układ: the new `StyleguideLayoutForm` with a header-action section, a nested section with a fieldset and footer action, and a collapsed section, followed by the in-form tabs;
    - Karta rekordu: nested record tabs, the infolist as tab 1;
    - Tabele i filtry: the list tabs and the table;
    - Akcje i modale: the new `StyleguideButtons` (kinds and every size), the modals and notifications, and badges.
  - Table row click now goes to `?obszar=karta-rekordu`. The page header actions are unchanged.
  - pint, `npm run build` and `composer check` pass (640 tests).
- 2026-11-06 (user, via manager): round 4 accepted. Round 5: page-level tabs get a soft active fill, mirroring the sidebar's fill + bar.
- 2026-11-06 (agent): round 5, `filament/schema/tabs.css`.
  - Scope selector: `.fi-tabs:not(.fi-contained):not(.fi-vertical):not(.fi-section .fi-tabs):not(.fi-sc-tabs.fi-contained .fi-tabs)`. It catches uncontained horizontal bars outside any section or contained Tabs card: the Styleguide areas, record tabs and list tabs. Tabs in a form or card keep the plain underline.
  - Active tab: `--primary-50` fill, top corners 6 px, the 2 px navy underline, navy semibold label, icon in royal.
  - Hover: half the active tint, darker text.
  - Page-tab items now have 12 px side padding and 4 px gaps.
  - All tab transitions now use the motion tokens, which zero out under reduced motion.
  - No sliding underline: Filament's tabs expose no indicator position, so it would need new Alpine or a vendor view.
  - `npm run build` and `composer check` pass (640 tests).
- 2026-11-06 (manager): user accepted 05 with these final tweaks by the manager:
  - page tabs: a glow rising from a 3 px inset navy indicator; weight 500 for inactive tabs, a hairline text-shadow for active; 8 px more space above the bar;
  - sidebar: 12 px between groups;
  - the active bar is back on the fill's leading edge (inset bar rejected).

  "Pulpit" replaces "Panel" (lang/vendor override). D-23 amended. Open for later: breadcrumbs or title in the empty topbar; sliding tab indicator (needs Alpine or a vendor view, only on the user's order).
