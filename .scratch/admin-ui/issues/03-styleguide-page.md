# 03 - Styleguide page on the default look

Status: ready-for-agent
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
