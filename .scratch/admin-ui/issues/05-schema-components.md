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
