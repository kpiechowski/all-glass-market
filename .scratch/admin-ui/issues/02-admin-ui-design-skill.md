# 02 - Write the admin-ui-design skill (rules and templates)

Status: resolved
Blocked by: 01

Spec: §2–§8, D-02, D-04..D-25, D-29, D-30. ADR 0005.

## What

Load `writing-for-agents` first. Write `.claude/skills/admin-ui-design/`:

- `SKILL.md`, short:
  - when to load it;
  - "name the template first";
  - the template table A–F linking to `templates/`;
  - the rules that apply to every screen: layout (a)/(b)/(c), the field pairing rule, the action hierarchy, colour = state, responsive grids, copy rules, the modification ladder, schema classes vs Tailwind;
  - the navigation consequence (D-25: new resources are added to the provider's navigation array);
  - a "Before you finish" checklist an agent can tick against its own code.
- `templates/A-list.md`, `B-form.md`, `C-record-view.md`, `D-settings.md`, `E-action-modal.md`, `F-dashboard.md` (sketch). Each holds:
  - a Filament v5 code skeleton (check APIs with `search-docs`);
  - detailed rules;
  - "not like this" examples taken from the current Users screens: the edit form grid, the addresses interleaved, the duplicated labels, four coloured header buttons, the 7-column table, empty columns, saturated category badges, dates with seconds.
- No `variants.md` yet (issue 10). Where a variant will apply, say "see variants (pending)".
- Reasons stay out. The ADR holds them.

## Done when

- The user has read `SKILL.md` and one template and accepts the tone and size.
- `SKILL.md` stays well under the length where an agent skims.

## Comments

### Worker — 2026-11-05
- **Built:** `.claude/skills/admin-ui-design/SKILL.md` (template table, layout a/b/c, fields, sections, responsive grids, action hierarchy, colour = state, copy, styling ladder + schema classes vs Tailwind, navigation consequence D-25, checklist) and `templates/A-list.md`, `B-form.md`, `C-record-view.md`, `D-settings.md`, `E-action-modal.md`, `F-dashboard.md` (sketch). Skeletons use Offer/Customer examples so the Users retrofit (issue 11) is not pre-solved; "not like this" cites the Users screens.
- **APIs checked in vendor/filament:** `hasCombinedRelationManagerTabsWithContent`, `getContentTabLabel`, `visibleFrom`/`hiddenFrom` (table columns), `FiltersLayout::AboveContent`, `$formActionsAreSticky` / `Actions::sticky()`, `slideOver`, `Section::headerActions/footerActions`, `columnOrder`, `toggleable(isToggledHiddenByDefault:)`, `since`/`dateTooltip`, `emptyState*`, `Table::isFiltered()`, `recordUrl`, `toolbarActions`, `NavigationBuilder`, `getHeaderWidgetsColumns`.
- **Spec gaps / notes:**
  - D-09/D-16 "header actions collapse into ⋮ on mobile except primary": actions have no `visibleFrom`/`hiddenFrom`; written as theme work.
  - D-14 "≤3 inline + rest behind the funnel": one table has one filters layout, so the skill says ≤3 → `AboveContent`, more → default funnel.
  - D-10 `infolist--sheet` and D-11 underlined tabs: marked "see variants (pending)".
  - Added for checkability: the template name and a layout (b) reason go in the class docblock.
  - D-13 "no seconds" kept to lists, as the spec says.
- **Deviations:** none beyond the above.

- 2026-11-05 (manager): user accepted. Change on review: table columns never inline in `configure()` — private `columns()` method, a separate `{X}TableColumns` past ~10 columns (wiring skill + A-list skeleton).
