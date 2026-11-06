# 11 - Retrofit Users and Audit from the skill

Status: ready-for-agent
Blocked by: 10

Spec: D-38, D-39, D-21, D-23, D-24.

## What

- A worker that has loaded only `admin-ui-design` and `filament-module-wiring` rebuilds:
  - Users: list, create, edit, view, profile;
  - Audit: central page, record history tab, technical log.
- The work includes:
  - enum colours to D-21;
  - copy to D-22;
  - sidebar badges to D-23 (amended): clear indicators, cheap or cached counts;
  - the "Dziennik" vs "Dziennik zmian" naming (D-24, with the user);
  - record view tabs (D-10).
- Every place the worker went wrong is logged under Comments as a skill gap. The skill is fixed first, then the code.

## Done when

- The user has accepted the screens.
- The skill gaps found are closed in `admin-ui-design`.
- `composer check` passes.
