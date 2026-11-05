# Admin UI — design rules, theme and styleguide

<!-- The authoritative reference for how admin panel screens are laid out and how the theme is built.
     Agreed in a grilling session; every rule carries a decision id (D-xx) so issues and the skill can trace back. -->

- **Agreed:** 2026-11-05 · **Branch at agreement:** `feat/audit`
- **Trigger:** the first module built by agents (Users) came out with broken layouts. The edit form split into a grid of boxes, fields were paired at random, the table had too many and half-empty columns, the badges were loud and the header actions competed. The existing theme CSS mixes overrides without structure.

## 1. Goal

Agents should build admin screens that are about 90% right on the first try. Two things get us there:

1. **A design skill, `admin-ui-design`.** It holds screen templates, layout rules, action hierarchy and table rules, written so an agent applies them every time. (D-01, D-02)
2. **A rebuilt theme with a small set of named variants.** We work the theme out on a local Styleguide page with the user reviewing by hand, then document the variants in the skill. (D-30..D-36)

What is out of scope:
- dark mode (D-28);
- a technical-table layout (logs) for mobile, which comes later (D-12);
- the dashboard template beyond a sketch (D-05).

## 2. Skills

- **D-01** `filament-module-ui` is renamed to **`filament-module-wiring`**. It keeps only the mechanics: commands from the UI, visibility, reading through `{X}Queries`, class split, namespaces and translations. Rules about how a screen looks move to the new skill, for example "2+ row actions → `ActionGroup`". The old skill points to the new one. References in `CLAUDE.md`, `.claude/agents/module-worker.md` and `.claude/skills/module-cqrs/SKILL.md` are updated.
- **D-02** The new skill is **`admin-ui-design`**:
  - `SKILL.md` is short. It holds the rules that apply to every screen, the template table with links, and a "Before you finish" checklist.
  - `templates/<X>-<name>.md` holds one file per screen template: a code skeleton, detailed rules and "not like this" examples drawn from the Users screens.
  - `variants.md` holds the variant catalogue and is written last (D-36).
  - The skill holds rules, not reasons. Reasons go to `docs/adr/` (ADR 0005).
  - It is written with the `writing-for-agents` skill.
- **D-03** Enforcement:
  - a row in the `CLAUDE.md` skill table;
  - `module-worker` loads `admin-ui-design` for every task that touches UI;
  - `filament-module-wiring`'s "Before you finish" points to the `admin-ui-design` checklist.

## 3. Screen templates

- **D-04** Every screen starts from one named template. Before laying anything out, the agent names the template it is building.

| | Template | Is |
|---|---|---|
| A | List | table, tabs for the main split, filters, bulk actions |
| B | Form | create / edit a record |
| C | Record view | infolist first, then tabs for audit and relations |
| D | Settings / single object | a page editing one object that is not a list record |
| E | Action modal | short form or confirmation |
| F | Dashboard | widgets; **sketch only** until the first real dashboard (D-05) |

- **D-05** Template F stays a sketch until a real dashboard exists. New templates are added only when a real module needs one.

## 4. Layout rules

### Page layout (D-06)

- **(a) One column of sections, stacked.** This is the default for forms and record views.
- **(b) Main column (~2/3) plus side column (~1/3).** This is an exception and needs a reason: the record has a status or metadata worth keeping in view, such as active, role, dates or who created it. The side column never holds ordinary input fields. On mobile it drops **above** the main content.
- **(c) Free grid of boxes.** Allowed only on analytical pages (statistics, logs, dashboards), usually with a custom layout. It is never used on a form or a record view.
- An agent that opens a form with two columns of sections without a reason has made a mistake.

### Fields (D-07)

- A field shares a row with another only when both are short and semantically paired, like City + Postcode or First + Last name. The default is one field per row.
- Field width follows the expected length of the data. Postcode, NIP and phone do not stretch across the section; use a 12-column grid and span to fit.
- The label always sits above the field. A placeholder only shows a format example ("00-000") and never replaces a label.
- `helperText` is at most one sentence. Longer explanations go into the section description.
- Fields that depend on another value appear and disappear reactively. They are not left empty and disabled.
- Fields are grouped by meaning. Two different addresses never interleave.

### Sections (D-08)

- No icon in a section heading by default. A section description is allowed.
- A section with one field named like the section hides that field's label (`hiddenLabel()`).
- A section collapses only for rarely used settings, never for required data.
- One capability appears in one place. "Password" is either an action or a section, not both.

### Mobile (D-09)

- Every grid is declared responsively: `->columns(['default' => 1, 'md' => 2])`. A bare `->columns(2)` is never used.
- Sections, forms and infolists must read cleanly on a phone.
- Page header actions collapse into "⋮" on mobile, except the primary one.

### Record view (D-10)

- Tab 1 is the infolist. Later tabs hold the audit history and relation data, loaded lazily so the page carries fewer live Livewire components. The native mechanism is `hasCombinedRelationManagerTabsWithContent()` with lazy relation managers.
- The infolist is laid out as label–value pairs in a **horizontal sheet**: a grey fixed-width label on the left, the value on the right. On mobile the label moves above the value. It is a variant, `infolist--sheet` (D-35).
- Empty values show "—" and are not hidden. If a whole group does not apply to the record (company data on a private account), the whole section is hidden.

### Tabs (D-11)

- **Page tabs** (list tabs, record view tabs) are underlined: one long line with the active tab marked.
- **Tabs inside a form** are used only for 4+ independent groups where no tab after the first holds a required field.
- Otherwise use stacked sections.

## 5. Tables (template A)

- **D-12** On mobile, a list table shows as **cards**:
  - regular columns are `->visibleFrom('md')`;
  - one `ViewColumn` "card" is `->hiddenFrom('md')` and renders the card;
  - theme CSS hides the table header and row lines on mobile;
  - no vendor template override.

  Native `Split`/`Stack` may be compared on the Styleguide as an alternative only. Technical tables such as logs are an exception, decided later.
- **D-13** Columns:
  - At most 5–6 columns are visible by default. The rest are `toggleable(isToggledHiddenByDefault: true)`.
  - Data that belongs together shares one cell: name + email through `->description()`, or a custom composite column when that is not enough.
  - A column that is mostly empty is not allowed. Its information becomes an indicator (icon or small badge) in the main cell, for example "must change password" next to the name.
  - Dates on lists have no seconds. Show the date, or relative time with the full date in a tooltip.
- **D-14** Filters:
  - Up to 3 simple, frequent filters sit inline above the table (`FiltersLayout::AboveContent`).
  - More filters go into the panel behind the funnel icon.
  - The main split of the data (Active / Inactive / Invited) is **list tabs**, not a filter.
  - The active-filter count badge shows only when it is above 0.
- **D-15** Every list table has its own empty state: icon, one sentence, and the primary action when the user may perform it. An empty search or filter result has a different message ("No results for these filters").

## 6. Actions

- **D-16** Page header:
  - at most **one primary** action (filled, primary colour);
  - at most **two secondary** actions (grey or outline);
  - the rest goes into an `ActionGroup` "⋮";
  - **dangerous** actions (Deactivate, Delete) are always in "⋮" as red text in the menu, never a red button in the header. Full red appears only in the confirmation modal;
  - warning and info colours never serve to highlight a button.
- **D-17** Table rows:
  - clicking the row opens the record (`recordUrl`);
  - one extra action may stand alone as an icon with a tooltip;
  - two or more go into "⋮".
- **D-18** Form footer: Save (primary) and Cancel (grey) are sticky at the bottom of the viewport.
- **D-19** Section actions (`Section::headerActions()` / `footerActions()`) are allowed when the action concerns that section only, like "Reset" on a password section or "Add address" on addresses. Use them with judgement; actions are not limited to header and footer.
- **D-20** Modals:
  - a modal is for a short task only: up to ~5 fields, or a confirmation;
  - longer forms go to their own page or a slide-over (`->slideOver()`), the latter when the user needs to see the list underneath;
  - a dangerous confirmation states plainly what will happen, and its button names the act ("Dezaktywuj"), never "Tak"/"OK".

## 7. Colour and copy

- **D-21** Badges:
  - categories (account type, role) are neutral or soft: light tint, dark text;
  - semantic colours (success, warning, danger) only mark operational states: active/blocked, needs action, error;
  - an empty badge is a bug; no value means "—" or no element.

  Domain enums implementing `HasColor` (ADR 0003) follow this rule. Users' enums are corrected in the retrofit (§11).
- **D-22** Polish copy:
  - labels and titles use sentence case ("Data dołączenia", not "Dziennik Zmian");
  - buttons are verbs in the infinitive, with an object when it clarifies ("Utwórz użytkownika", "Zapisz", "Dezaktywuj");
  - notifications use the past tense and stay short ("Zapisano zmiany");
  - error messages say what to do, not only what is wrong.
- **D-23** Sidebar badges only count things **to do** (pending, new requests). An informational badge such as "i" on "Mój profil", or a count that needs no action, is not shown.
- **D-24** Navigation items must not be confusable. "Dziennik" next to "Dziennik zmian" is a naming problem for the retrofit, not a CSS problem.

## 8. Navigation

- **D-25** The sidebar has a **top** and a **bottom** part:
  - the panel provider builds navigation explicitly with `->navigation(fn (NavigationBuilder $builder) => ...)`, using its own groups and items;
  - the two main blocks stretch over the full sidebar height, and the bottom block (profile, later settings) is aligned to the bottom;
  - for now it is a plain array in `AdminPanelProvider`. The user will extract it to a class later.

  Consequence: a new module resource or page does not appear in the sidebar until it is added there. The skill states this.

## 9. Theme CSS

- **D-26** The theme is rewritten from scratch. Kept: the directory split, the primary `#003eb2` and the Vite entry `resources/css/admin/theme.css`.
- **D-27** Files are split by origin and need:

```
resources/css/admin/
  theme.css          imports + @source only (vendor theme, our files)
  tokens/            our variables: colours, typography, spacing, radii, shadows
  base/              globals, base typography
  filament/          overrides of native fi-* components, one file per component
    layout/          sidebar, topbar, page-header, breadcrumbs
    schema/          section, tabs, fieldset, grid
    forms/           input, select, checkbox-toggle, rich-editor
    infolists/       entry
    tables/          table, filters, pagination, empty-state
    actions/         button, action-group dropdown, modal
    misc/            badge, notification, widget
  components/        CSS for our own Blade components (card column, person column…)
  pages/             page-specific CSS (login, styleguide)
```

  A variant lives **in the same file** as its component (`filament/schema/section.css` holds `.section--compact`). There is no `variants/` directory.
- **D-28** No dark mode: `->darkMode(false)` stays, and the client does not want one.
- **D-29** Classes and utilities:
  - Filament schema components (`extraAttributes`, `extraInputAttributes`…) get **only our variant classes**, never Tailwind utilities;
  - Tailwind utilities are allowed only in our own Blade views;
  - module CSS is **not forbidden**: styling defaults to the theme, and a module may register its own asset only for styles that truly belong to that module alone.
- **D-30** Modification ladder, safest first:
  1. CSS on native `fi-*` classes;
  2. variant classes through `extraAttributes()`;
  3. own Blade components, `ViewColumn` / `ViewEntry`;
  4. overriding a published vendor view, **only on the user's explicit order**, and listed in docs.
- **D-31** Visual direction: quiet and modern.
  - It must not look like a 2010 or WordPress-era panel, and it is "not a disco".
  - The base is neutral greys with thin borders rather than heavy shadows.
  - The client colour is an accent: primary action, active navigation, focus.
  - Medium density: table rows ~48–52 px, base text 14 px, one font family.

  The exact look is settled on the Styleguide.
- **D-32** Font: Poppins is replaced. **Roboto, Inter and Manrope** were compared live on the Styleguide. **Manrope won** (2026-11-05): it is precise and lighter. Numbers in tables use tabular figures.
- **D-40** Brand colours, taken from the client's logo and website:
  - navy `#001B4E` (logo text): the darker accent;
  - royal `#003eb2`: primary;
  - `#0070C0` (logo stars): light accent.

  The site uses dark navy blocks, royal-blue buttons and simple geometry (chamfered corners). The admin uses the navy as a stronger accent than the first theme did. The logo sits in the panel (`->brandLogo()`).
- **D-41 (open: ask the user)** Accenting the sidebar (navy and/or decorative accents), and whether the site's chamfered-corner geometry carries into buttons or containers. The user will bring this up. Ask before closing issue 09.

## 10. Styleguide

- **D-33** Page `app/Core/UserInterface/Filament/Pages/Styleguide.php`:
  - registered explicitly in `AdminPanelProvider` when `app()->isLocal()`, and nowhere else;
  - static data only: the table uses `->records(fn () => [...])`, the form keeps page state and saves nothing;
  - its demo copy is hard-coded Polish, the one exception to "every string from translations";
  - it is permanent, as the reference and a regression check.
- **D-34** It shows every main component on one page: page header with actions, sections, tabs, form fields of every common type, an infolist, a table with filters, row actions, bulk actions, the empty state and the mobile card, badges, a modal, a slide-over and a notification trigger. It carries a **font switcher** (Roboto / Inter / Manrope) that loads the three fonts and swaps the font variable on `<html>`. The switcher is removed once the font is chosen.
- **D-35** The first variants, built after the base style is accepted:
  - **Section:** default, `section--compact` (tighter, for nested sections), `section--flat` (no frame, heading line only, for grouping inside another section).
  - **Tabs:** `tabs--underline` (page and record tabs), `tabs--pills` (switcher inside a section).
  - **Infolist:** `infolist--sheet`.
  - **Table:** default with a mobile card.

  More variants come only when a real module needs one.
- **D-36** After the variants are accepted, `variants.md` in the skill documents each variant: when to use it, and how to apply it.

## 11. Process

- **D-37** Review loop: a subagent changes CSS and reports what changed. The user reviews the Styleguide in the browser and sends screenshots or notes. No agent clicks through the panel (Livewire round-trips are slow, and the user is faster).
- **D-38** Order (issues):
  1. rename the skill;
  2. write the `admin-ui-design` skill (rules and templates, no variants);
  3. Styleguide on the default look;
  4. tokens, base, layout and navigation, with the font chosen; buttons were pulled forward into 04 at the user's request (2026-11-05);
  5. schema components;
  6. forms;
  7. infolists;
  8. tables;
  9. actions, modals, badges and notifications;
  10. variants and `variants.md`;
  11. retrofit Users and Audit from the skill alone.

  Style issues run one at a time, and each ends with the user's review.
- **D-39** The retrofit tests the skill. Every mistake the agent makes there is a gap in the skill, so the skill is fixed first and the code second.
