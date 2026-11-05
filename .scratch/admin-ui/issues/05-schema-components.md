# 05 - Schema components: section, tabs, fieldset, grid

Status: ready-for-agent
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
