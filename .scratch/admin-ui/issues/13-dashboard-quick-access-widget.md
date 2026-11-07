# 13 - Dashboard widget "Szybki dostęp" (per-user quick access)

Status: resolved
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

**Decided (user, 2026-11-06): option C.** A small `Preferences` module, built to grow; the registry contract lives in Core. Record it in an ADR when the work starts.

## Notes for the design

- Store item **keys** in the JSON, never URLs or class names. Keys survive refactors; URLs and visibility are resolved from the registry at render time.
  - Unknown or no-longer-accessible keys are skipped silently.
  - The handler enforces the 10-item limit and the "registered keys only" rule.
- **Decided:** a user with no preference sees an **empty grid** with an "Add" action.
  - Later, a small **onboarding** feature (its own issue) gets a step that pre-fills the grid with 3–4 suggested items, and the user confirms or changes them there.
  - So each registry item can carry a "suggested" flag, possibly per role. The onboarding step saves through the same `SavePreference` command.
- One registry also feeds 12 (the topbar hub), which shows all items grouped. So each item needs a group too.

## Decided in grilling (user, 2026-11-07), ADR 0006

- **Q1 Code-defined preferences.** Every preference is a class implementing a Core contract (`PreferenceDefinition` or similar): key, default value, validate-and-normalise. The handler resolves the definition by key and rejects unknown keys. The table stays generic (`user_id`, `key`, JSON `value`, unique per user+key). The first definition is `QuickAccessPreference`: an ordered list of registry item keys, max 10, no duplicates, registered keys only.
- **Q2 ModuleApi only.** Other modules read and write through `Preferences/Application/ModuleApi/UserPreferences` (`get(userId, key)`, `save(actor, key, value)` which sends the command) and register their definitions through the Core contract. Preferences knows no modules. The quick-access widget is the first client and lives in Preferences' UserInterface.
- **Q3 Per user only, defaults in code.** No global or per-role rows. A user without a row gets the definition's default (empty list for quick access, i.e. the empty grid). Suggested items for onboarding are a flag on registry items, not in Preferences. Preferences stay on deactivation; a cleanup listener only if Users gains hard deletion.
- Deferred: whether onboarding progress is a preference or its own module (decided with the onboarding ticket). The registry item contract carries a `group` now, for 12.

## Done when

- The placement is agreed and recorded (ADR).
- The widget works per user, drag & drop included, and the look is accepted on the Styleguide or Dashboard.
- `composer check` passes, and handler and entity tests are written test-first.

## Comments
- 2026-11-06 (manager): the user chose option C, a small `Preferences` module with the registry contract in Core. Start state: an empty grid. The 3–4 suggested items come later, through an onboarding step (a separate future feature).
- 2026-11-07 (manager): short grilling with the user, three decisions recorded above and in `docs/adr/0006-preferences-module-with-code-defined-preferences.md`. Branch `feat/quick-access` (from `feat/admin-ui`).
- 2026-11-07 (agent): implemented, not committed. The Core contract is `Core\Contracts\PreferenceDefinition`. The registries `Core\Domain\Preferences\PreferenceDefinitions` and `Core\Domain\QuickAccess\QuickAccessItems` are singletons, fed from module providers' `boot()`. `FilamentQuickAccessItem::for()` builds an item from a page or resource: navigation label and icon, `getUrl()`, and `canAccess()`, all resolved at render time. The `Preferences` module has the `user_preferences` table, the `UserPreference` entity, its repository, `SavePreferenceCommand` (the owner is the actor; the system is refused; an unknown key is refused), the ModuleApi `UserPreferences` (`get`/`save`) plus `QuickAccess` (`keysFor`, constants), and the `QuickAccessPreference` definition (max 10, no duplicates, registered keys only). Registered items: `users.list`, `users.journal`, `users.profile`, `audit.log`, `audit.technical_log`. The Pulpit is not registered, because a tile on the dashboard that points to the dashboard is pointless; it can be added for 12. The `QuickAccessWidget` sits on the dashboard. Drag & drop uses Filament's own `x-sortable` directive (SortableJS in `support.js`), so there is no new dependency and no vendor override. Every change saves at once through `CommandSender`. The CSS is in `resources/css/admin/components/quick-access.css`. `composer check` is green (703 tests); the migration ran on the dev DB. Note: the workspace container cannot reach MySQL (it is not on the `backend` network), so I ran the migration from `php-fpm` as uid 1000.
- 2026-11-07 (agent, round 2): built, not committed. (1) The add modal (`AddQuickAccessItemAction`) and the dashed "Dodaj" tile are gone. In edit mode a "Dostępne" palette unfolds under the grid: groups with icon, label and count, and mini tiles (icon, label, a "+" disc) that are buttons with an aria-label. A click adds the item at the end (`QuickAccessWidget::add()`); the ✕ sends it back (`remove()`). Both have short enter and leave animations. At the limit the palette dims and shows "Osiągnięto limit 10 kafelków — usuń któryś, aby dodać nowy."; with nothing left it shows one muted line. The empty state's "Dodaj" (`startAdding`) opens edit mode with the palette. (2) Groups are registered objects in Core: `QuickAccessGroup` (key, label translation key, icon), via `QuickAccessItems::registerGroup()`. An item's `group` is now the group key, and an item whose group is not registered is refused. `inGroups()` sorts items under their groups for 12. Users registers `users` (OutlinedUsers) and Audit registers `audit` (OutlinedClock). (3) Tiles wiggle in edit mode: ±0.6°, 0.3 s alternate, five phases and three durations by position. They stand still on hover, on focus and while dragged. Under reduced motion there is no wiggle and the tiles get a dashed primary-300 edge. "Gotowe" is primary with a check icon. (4) Every save keeps registered keys the user cannot open now, in their place; they count towards the 10. Unregistered keys are dropped. Covered in `QuickAccessWidgetTest`. `composer check` green (714 tests).
- 2026-11-07 (manager, closing): accepted by the user after round 2 (inline palette with group icons, wiggle in edit mode, hidden tiles kept). Left for later: the palette folds closed instantly on "Gotowe" (a smooth close needs client-side edit mode); focus after "+"/✕ falls back to the page; `Domain/Definitions/` is not yet listed in the `module-layout` skill; the workspace container is not on the MySQL network (migration ran from php-fpm).
