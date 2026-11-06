# 13 - Dashboard widget "Szybki dostęp" (per-user quick access)

Status: needs-triage
Blocked by: 11. Decide the placement (below) before any code. Related: 12 (the topbar hub uses the same registry of links).

## What (the user's idea, 2026-11-06)

- A dashboard widget titled "Szybki dostęp": a section heading plus a grid of icon tiles, 4–5 per row, **at most 10 items** (2 rows).
- **Per user:** each user chooses their own tiles.
- A header action switches the widget into **edit mode**. In edit mode the user can:
  - **reorder the tiles by drag & drop**;
  - add a tile from the list of available items;
  - remove a tile.
- **The list of available items is registered from code**, not typed in by users. Each module registers its pages through a small public interface: a key, a label, an icon and a URL.
  - We register only our own pages, so the icon is the page's own navigation icon.
  - Visibility follows the page's `canAccess()`.
- The save is safe: a user can store only the keys of registered items. A URL never comes from user input.
- It is a custom component, because Filament has no tile grid with drag & drop. Filament already ships SortableJS (`x-sortable`, used by repeaters and table reordering), so drag & drop needs no new dependency. Check this in the vendor JS before building.
- Storage: a JSON column.

## Placement: options (to decide with the user)

The CLAUDE.md rule applies: "a shared need goes into Core or becomes a small module". There are two separate parts:

- **the registry of items**, which every module feeds;
- **the stored preference**, which is a write: a command, a handler and a table.

| | Option | Pros | Cons |
|---|---|---|---|
| A | **Users module**: a `quick_access` JSON column on `users`, a `SetQuickAccessCommand`, the widget in Users' UserInterface, the registry contract in Core | Fewest classes; the data sits next to its owner | Mixes UI preferences into the identity aggregate and the `users` table. Users becomes the home of every future preference (column toggles, default filters…). |
| B | **Core feature**: the registry, a `user_panel_preferences` table, a command and handler, and the widget all live in Core | The widget is a panel-wide thing, and Core already hosts the panel pieces (Styleguide, navigation) | Core becomes a place with business data and a write path, but it is meant for contracts and base classes. Deptrac rules for Core would need loosening. |
| C | **A small new module `Preferences`** owns a `user_preferences` table (`user_id`, `key`, JSON `value`, unique per user+key), a `SavePreference` command and a `UserPreferences` ModuleApi; the widget lives in its UserInterface. **The registry contract lives in Core** (`Core\Contracts\QuickAccessItem` or similar), so modules register items without depending on Preferences. | Fits the architecture rule as written. Reusable for every later per-user setting (the "users link meta" idea, generalised). Users stays about identity. User deletion is handled by a listener on the Users event. | One more module's boilerplate (provider, migration, entity, mapper, repository, handler). There is a cross-module link to `users` (an id, not an FK relation in the domain). |

**Manager's recommendation:** C, with the registry contract in Core. Write an ADR once it is agreed.

## Notes for the design

- Store item **keys** in the JSON, never URLs or class names. Keys survive refactors; URLs and visibility are resolved from the registry at render time.
  - Unknown or no-longer-accessible keys are skipped silently.
  - The handler enforces the 10-item limit and the "registered keys only" rule.
- Default for a user with no preference: an empty state with an "Add" action, or a default set per role. Ask the user.
- One registry also feeds 12 (the topbar hub), which shows all items grouped. So each item needs a group too.

## Done when

- The placement is agreed and recorded (ADR).
- The widget works per user, drag & drop included, and the look is accepted on the Styleguide or Dashboard.
- `composer check` passes, and handler and entity tests are written test-first.

## Comments
