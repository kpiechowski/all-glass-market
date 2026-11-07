---
name: admin-ui-design
description: "How admin panel screens look: screen templates, layout, field pairing, action hierarchy, table columns, badge colours, Polish copy and theme CSS. Use when building or changing any Filament screen, or any file under resources/css/admin."
---

# Admin UI design

Mechanics (commands, visibility, `{X}Queries`, class split, translations) are `filament-module-wiring`. This skill decides what the screen looks like.

## Name the template first

Before laying anything out, write down which template the screen is, then open its file and start from its skeleton.

| | Template | Is | File |
|---|---|---|---|
| A | List | table, list tabs, filters, bulk actions | [templates/A-list.md](templates/A-list.md) |
| B | Form | create or edit one record | [templates/B-form.md](templates/B-form.md) |
| C | Record view | infolist first, then tabs for audit and relations | [templates/C-record-view.md](templates/C-record-view.md) |
| D | Settings | a page editing one object that is not a list record | [templates/D-settings.md](templates/D-settings.md) |
| E | Action modal | short form or confirmation | [templates/E-action-modal.md](templates/E-action-modal.md) |
| F | Dashboard | widgets; sketch only | [templates/F-dashboard.md](templates/F-dashboard.md) |

A screen that fits none of them: stop and ask the user. A new template is added only for a real module.

## Page layout

- **(a) One column of stacked sections.** The default for forms, record views and settings.
- **(b) Main column (2/3) + side column (1/3).** Only when the record has status or metadata worth keeping in view (active, role, dates, author). The side column holds no ordinary input fields, and on mobile it sits above the main content. Write the reason in the class docblock.
- **(c) Free grid of boxes.** Analytical pages only (statistics, logs, dashboards).

## Fields

- Form fields: one field per row. Two fields share a row only when both are short and belong together: City + Postcode, First + Last name. Infolist entries follow the sheet rules of [template C](templates/C-record-view.md).
- Width follows the data: a 12-column grid, and short values (postcode, NIP, phone) span only what they need.
- Label above the field. A placeholder shows only a format example ("00-000").
- `helperText` is one sentence at most; longer text goes into the section description.
- A field that depends on another value appears reactively (`->live()` + `->visible()`), never sits there empty and disabled.
- Group by meaning: one address is one section or block, and two addresses never interleave.

## Sections

- Heading with no icon. A description is fine.
- A section whose only field is named like the section hides that field's label (`->hiddenLabel()`).
- Collapse only rarely used settings, never required data.
- One capability lives in one place: "Hasło" is an action or a section, not both.
- `Section::headerActions()` / `footerActions()` hold an action that concerns that section only ("Resetuj", "Dodaj adres").

## Responsive grids

Every grid declares its breakpoints: `->columns(['default' => 1, 'md' => 2])`, `->columnSpan(['default' => 'full', 'md' => 4])`. Sections, forms and infolists read cleanly on a phone.

## Action hierarchy

- **Page header:** one primary action (filled, primary colour), at most two secondary (grey), everything else in one `ActionGroup` "⋮". On mobile only the primary stays outside "⋮"; the theme does that, not the page class.
- **Dangerous actions** (Dezaktywuj, Usuń) live in "⋮" as red menu items. Full red appears only on the confirmation button.
- `warning` and `info` colour states, never buttons.
- **Table row:** the row click opens the record (`recordUrl`). One more action may stand alone as an icon with a tooltip; two or more go into "⋮".
- **Form footer:** Zapisz (primary) and Anuluj (grey), sticky at the bottom.
- **Modal:** up to ~5 fields or a confirmation. Longer goes to its own page, or a slide-over when the list underneath must stay visible.

## Colour = state

- Categories (account type, role) are `gray` or a soft tint with dark text.
- `success`, `warning`, `danger` mark operational states only: active/blocked, needs action, error.
- No value means "—" or no element; a badge is never empty.
- A domain enum's `getColor()` (ADR 0003) follows the same rule.
- A sidebar badge is a count or an attention indicator ("!", warning, gear). Filament evaluates every navigation badge on every page load, so a count is cached or comes from a small indexed query, never a heavy count. If it cannot be cheap, show an indicator instead.

## Copy (Polish)

- Labels and titles in sentence case: "Data dołączenia".
- Buttons are infinitive verbs, with an object when it helps: "Utwórz użytkownika", "Zapisz", "Dezaktywuj". A confirmation button names the act.
- Notifications in the past tense, short: "Zapisano zmiany".
- An error message says what to do next.
- A section heading and a field label inside it never repeat each other; neither do two navigation items ("Dziennik" vs "Dziennik zmian").

## Styling

Climb this ladder and stop at the first rung that works:

1. CSS on native `fi-*` classes in `resources/css/admin/filament/<area>/<component>.css`.
2. A variant class through `->extraAttributes(['class' => '…'])` — see variants (pending).
3. Own Blade component, `ViewColumn` or `ViewEntry`, with its CSS in `resources/css/admin/components/`.
4. Overriding a published vendor view: only on the user's explicit order, and listed in docs.

Corners stay rounded with the theme radii: no chamfered shapes and no decorative accents beyond the topbar ornament and the login geodes.

Schema components (`extraAttributes`, `extraInputAttributes`…) receive only our variant classes. Tailwind utilities belong only in our own Blade views. A module registers its own CSS asset only for styles that belong to that module alone.

## Navigation

The panel builds the sidebar explicitly in `AdminPanelProvider` (`->navigation(fn (NavigationBuilder $builder) => …)`). A new resource or page appears in the sidebar only after you add its item there.

## Before you finish

- The template is named in the page or resource class docblock, and the screen follows its file.
- Every form and record view is layout (a), or (b) with its reason written down.
- Every shared row is a semantic pair; no address interleaves with another.
- Every `->columns()` and `->columnSpan()` has a `default` breakpoint.
- No section heading has an icon; no section label is repeated by its only field.
- Each capability appears once on the screen.
- The page header has ≤1 primary, ≤2 secondary, and every dangerous action inside "⋮".
- Every table row opens its record by click; 2+ row actions sit in one `ActionGroup`.
- Every badge colour marks a state, and every category badge is neutral.
- Every list date omits seconds; every empty value shows "—".
- Every label, button and notification follows the copy rules.
- No Tailwind utility sits on a schema component; no vendor view is overridden.
- A new resource or page has its sidebar item in `AdminPanelProvider`.
- Every "Before you finish" item of the template file holds.
