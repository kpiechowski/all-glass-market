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
