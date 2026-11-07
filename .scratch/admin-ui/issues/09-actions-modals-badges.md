# 09 - Actions, modals, badges and notifications

Status: resolved
Blocked by: 08

Spec: D-16..D-21, D-27, D-31, D-37.

## What

- `filament/actions/`: primary, secondary (grey/outline), link and icon buttons, "⋮" trigger and dropdown with a danger item, modal, slide-over, the dangerous-confirmation modal, sticky form actions.
- `filament/misc/`: soft category badges against semantic state badges (no empty badge renders), notifications, widgets basic.

## Done when

- The user has accepted the look on screenshots.
- `npm run build` and `composer check` pass.

## Comments

- 2026-11-05 (manager): buttons were pulled forward into 04. Here, only check them against modals and dropdowns. **Before closing 09, ask the user about accenting the sidebar and about chamfered-corner geometry (spec D-41).**
- 2026-11-06 (manager, topbar ornament side track): the user tried a faceted "geode" ornament in the topbar corner by the sidebar. It was dropped there: too cramped in 56 px, it read as busy and slightly kitsch. The topbar gets straight parallel "glass pane" cuts instead (`app/Core/UserInterface/resources/views/filament/topbar/ornament.blade.php`). **The geode moves to the login screen:** one pair of geodes in opposite page corners (e.g. top-left and bottom-right), not more. Accepted geometry to start from, a 120 x 56 viewBox symmetric about the horizontal middle of the left edge, with polygons painted in this order:
  - fade pair: `0,0 120,0 0,112` and `0,56 120,56 0,-56`, filled with a horizontal gradient of sky-light 16 % to 0 from x = 60;
  - sky-light 22 %: `0,0 90,0 0,84` and `0,56 90,56 0,-28`;
  - royal 38 %: `0,0 60,0 0,56` and `0,56 60,56 0,0`;
  - navy: `0,0 30,0 0,28` and `0,56 30,56 0,28`.

  Rules the user set: every vertex sits on a wall (no spikes from a single side), and edges run either into a wall or parallel to each other. On the login page the shape can be larger and rotated per corner. The user's remark: the jump from navy to the middle tone is too big, so soften it.
- 2026-11-06 (agent): first pass built, awaiting the user's review on `/admin/styleguide?obszar=akcje` and `/admin/login`.
  - New CSS: `filament/actions/dropdown.css`, `modal.css` and `form-actions.css`, plus `filament/misc/badge.css`, `notification.css` and `widget.css`. Motion token `--ag-duration-slow: 260ms` added (zeroed under reduced motion).
  - Dropdown: 1 px gray-200 ring + shadow-lg, gray-100 hover. Danger item: danger-700 text, danger-600 icon, danger-50 hover. A hairline separates groups. Opens with a fade and grows from 98 % (200 ms). The page-header "⋮" wears the grey button frame (36 px).
  - Modal: navy 36 % overlay with a 2 px blur, 18 px bold navy heading, a hairline above the footer when the modal holds a form. Open: fade + 98 % + 6 px rise (200 ms). Confirmation icon sits on a 50 tint with a 100 edge, so red appears only in the icon and the confirm button. Slide-over: hairline header and footer, slides in over 260 ms.
  - Sticky form actions: a white bar with a ring + shadow-lg, lifted 16 px with a 10 px radius from md, rising 8 px into place.
  - Badges: categories (gray, soft primary) have no dot. States (success/warning/danger/info) take the 50 fill, 700 text and 200 edge, plus a 6 px dot in the 500 shade. Counters (sidebar, icon-button, tabs, menu) get no dot.
  - Notifications: one white card, the status shown in the icon (600) and a 3 px leading bar (500). Slides in 16 px over 260 ms.
  - Stat widget: section edge, 28 px bold navy value, 700 trend colour.
  - Login geode: `core::filament.auth.geode` via the `SIMPLE_LAYOUT_START` hook (so it shows on every simple auth page). It carries the topbar geometry into a corner: pairs of triangles that share the corner, every vertex on a wall, hypotenuses parallel. An extra royal 62 % step between navy and royal 38 % softens the jump. Placed top-left and bottom-right (rotated 180°), clamp(7rem, 16vw, 16rem), behind the card.
  - Styleguide "Akcje i modale" tab reorganised: buttons, the "⋮" menu (the header uses the same grouped menu: ordinary items, then Dezaktywuj + Usuń), modals, four notification triggers, categories vs states badges, stat widget.
  - Open: D-41 chamfered corners not touched. The mobile rule "only the primary stays outside ⋮" is not done (CSS cannot move actions into the menu). Resource pages are not yet `->sticky()` (retrofit).
- 2026-11-07 (agent, round 2): the `button--chamfer` variant is in `filament/actions/button.css`, for the user to decide on (D-41).
  - Shape: a primary button whose top-right corner is cut by a straight 45° diagonal, 10 px along each edge.
  - How it is drawn: the button itself is not clipped. Its fill is a `::before` clipped with `clip-path: polygon()`, royal turning navy on hover and active over `--ag-duration-fast` / `--ag-ease-out`.
  - Focus: an `::after` 4 px outside the button, clipped with an evenodd polygon to a 2 px navy band that follows the edges and the cut. It keeps the regular 2 px offset and fades in on `:focus-visible`.
  - Styleguide: "Akcje i modale" › "Wariant: ścięty narożnik" shows the regular "Zapisz" next to a chamfered "Zapisz" and a chamfered "Zaproś użytkownika" with an icon. Other buttons are unchanged.
- 2026-11-07 (manager, closing): accepted by the user. The manager's own tweaks: notification leading corners square, 4 px on the other side; login geodes enlarged to `clamp(10rem, 28vw, 28rem)`. The `button--chamfer` trial was **rejected** and removed (D-41: no chamfers, no further accents). Recorded as D-41 and D-46 in the spec. Left for later: on mobile only the primary header action outside "⋮" (needs PHP); sticky form actions on resource forms (retrofit 11).
