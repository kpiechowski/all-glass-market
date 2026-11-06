# 09 - Actions, modals, badges and notifications

Status: ready-for-agent
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
