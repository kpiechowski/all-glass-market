# 03 - Styleguide page on the default look

Status: resolved
Blocked by: 02

Spec: D-33, D-34, D-32.

## What

- `app/Core/UserInterface/Filament/Pages/Styleguide.php` plus its Blade view, registered in `AdminPanelProvider` only when `app()->isLocal()`.
- Static data only: the table uses `->records(fn () => [...])` and the form uses page state with no save. Hard-coded Polish demo copy is allowed here (D-33).
- Sections, in this order:
  1. page header with one primary, two secondary and a "⋮" group with a danger item;
  2. a form in template B: sections, paired short fields, a 12-column grid, every common field type (text, select, multi-select, checkbox list, toggle, radio, date, textarea, rich editor, file upload), helper text, a validation-error state, a collapsible section, section header actions;
  3. tabs: page-level and in-form;
  4. an infolist in template C, including empty values;
  5. a table in template A: list tabs, inline filters, a filter panel, a description cell, an indicator in the main cell, soft and semantic badges, a single icon row action, a "⋮" row group, bulk actions, pagination, the empty state (a toggle switches to no records), and the mobile card column;
  6. triggers for a modal, a slide-over, a dangerous confirmation and a notification.
- A font switcher: Roboto / Inter / Manrope loaded from the font provider Filament uses, swapping the font variable on `<html>`, remembered in `localStorage`.
- No theme CSS changes in this issue. It shows today's look.

## Done when

- `/admin/styleguide` renders locally and returns 404 outside `local` (test).
- `composer check` passes.
- The user has opened it and confirmed every block is present.

## Comments

**2026-11-05, agent**

- Page `app/Core/UserInterface/Filament/Pages/Styleguide.php` (`/admin/styleguide`), registered in `AdminPanelProvider` inside `if (app()->isLocal())`. Pieces split per `filament-module-wiring` under `Pages/Styleguide/`: `Schemas/` (StyleguideForm = B, StyleguideSettingsForm = in-form tabs, StyleguideTabs = page tabs, StyleguideInfolist = C), `Tables/` (StyleguideUsersTable = A, StyleguideUsersTableFilters, DemoUsers static rows), `Actions/` (InviteUser modal, EditPricing slide-over, DeactivateUser dangerous confirmation, DemoNotification).
- Core had no view namespace: added `loadViewsFrom(.../UserInterface/resources/views, 'core')` in `CoreServiceProvider`. Views: `core::filament.pages.styleguide` + `styleguide/{font-switcher,list-tabs,user-card}`.
- Nothing is persisted, so no commands: every action only sends a notification; forms call `validate()`, never `getState()`, so uploads are never stored. Hard-coded Polish copy is noted in the class docblock (D-33).
- Table on `records()` with search, list tabs (Aktywni/Nieaktywni/Zaproszeni/Wszyscy), 3 inline filters, sort, pagination (5/10/25) applied in memory. Two grey table header switches: "Pokaż pusty stan" and "Filtry w panelu" (swaps `AboveContent` ↔ `Dropdown` to show the filter panel). Row click goes to `#karta-rekordu` (no record page exists).
- NIP is prefilled invalid and its error is added on mount; "Zapisz" validates for real.
- Font switcher: Bunny Fonts link for Roboto/Inter/Manrope, sets `--font-family` on `<html>` (the variable Filament's base layout declares on `:root`), remembered in `localStorage` (`styleguide:font`), try/catch around storage. `->font()` untouched.
- No CSS file changed and no build needed: the card and switcher use Filament Blade components plus a few inline styles, because `app/Core/UserInterface/resources/views` is not a Tailwind `@source` in `theme.css` yet. Adding that line is a follow-up for 04+.
- `infolist--sheet` class is set on the infolist sections as in template C; it has no CSS yet (variants pending), so the look is unchanged.
- Test `tests/Feature/Core/UserInterface/Filament/Pages/StyleguideTest.php`: 404 outside local, full render locally, empty-state switch, list tab, demo save validation. Tests named `*_locally` boot the app with `env=local` via `afterBootstrapping(LoadConfiguration)`.
- `composer check`: pint pass, deptrac 0 violations, 633 tests passed.
