# 14 - Topbar navigation: a page's related views in the topbar (pilot: "Historia i logi") + SPA mode

Status: ready-for-agent

Spec: this file is the spec (no separate functional spec). New decision: **D-49** (write it, see "Docs").
Skills to load: `filament-module-wiring`, `admin-ui-design`, `module-layout`, `module-testing`.

## Why

The left part of Filament's topbar is empty. A reference design ("Qurtubiks") puts the page's related views
there (`Overview · Plan · Tasks · Gradebook · Students`, underlined active item), with breadcrumb and H1 below
in the content. With such a bar, one sidebar item can hold several separate but related screens, e.g. a future
"Oferty" page with a second item "OLX" (a related listing that is a page of its own), and the content gains a row.

## Decisions (grilling 2026-11-15)

1. **A topbar navigation is its own concept, not Filament's sub-navigation.** `getSubNavigation()`, record
   sub-navigation and clusters stay untouched and unused by this mechanism. A page shows a bar only when it
   declares one. Mainly main pages will do so.
2. **One bar = one class** listing its items. Every page that shows the bar points at that class (interface + trait).
   Several pages share one bar ("Oferty" and "OLX" show the same bar, each with its own active item).
3. **Items are links**, never Livewire toggles. An item points at a page class, optionally with query
   parameters (`HistoryAndLogs` + `zakladka=zadania`), or at another page ("OLX").
4. **The bar never touches H1 or breadcrumbs.** Every page keeps Filament's default heading and breadcrumbs.
   It changes only the browser `<title>`, and only when the active item is a variant of the *same* page:
   `"Nieudane zadania · Historia i logi"`. An item pointing at another page leaves that page's title alone.
5. **Sidebar:** while on any page of a bar, the sidebar item of the bar's **owner page** is active. Pages reached
   only through a bar do not get their own sidebar item.
6. **Active item** = current page class matches the item's page class **and** the item's declared parameters
   match the current request. Other query keys (filters, sort, pagination) are ignored. An item may override
   this with its own condition.
7. **Visibility:** an item the user may not open (`Page::canAccess()`, plus an optional `visible()` condition)
   is dropped. **Fewer than 2 visible items: no bar at all.** The bar is a convenience, not a guard; every page
   still re-checks access on entry (D-24a stays as is).
8. **Item content:** a translated label (module translation files) and an optional count badge. No icons.
   The badge is computed on render, with no polling.
9. **Overflow:** a rule in the skill (at most ~5 items). When they still don't fit, the bar scrolls horizontally
   (`overflow-x: auto`, `scroll-snap`, hidden scrollbar). On render, the active item scrolls to the centre
   (`scrollIntoView({ inline: 'center', block: 'nearest' })`, one Alpine `x-init`), so a clicked edge item
   slides to the centre and shows its neighbours.
10. **Look:** the `tabs--underline` typography; the active item's underline sits on the **bottom edge of the
    topbar**, in primary. The D-44 ornament stays at the left edge and the bar starts after it. Global search,
    notifications and avatar stay on the right; the bar takes the space between.
11. **Tabs under H1 remain a valid pattern.** A page may have a topbar bar *and* in-page tabs (topbar = related
    views or screens, tabs under H1 = splitting one already-grouped resource). For "Historia i logi" the page tabs
    **move** to the topbar.
12. **Mobile (< lg):** the bar is hidden in the topbar and the same items render **at the very start of the content,
    above breadcrumbs and H1**, as a horizontally scrolling underlined strip (same component, same auto-centre).
    The user will make the final call when there are real bars with real item counts. Keep the mobile placement
    behind one CSS class, so it is cheap to move.
13. **SPA mode is switched on for the whole panel** (`->spa()`) in this ticket. Add `spaUrlExceptions()` only for
    what breaks during the user's manual check (list below).

## Facts that drive the design (verified in vendor, Filament v5.11, Livewire v4.4)

- The topbar is its own Livewire component (`Filament\Livewire\Topbar`), rendered by the layout after the page.
  Its inner hooks (`TOPBAR_START`, `TOPBAR_LOGO_AFTER`, `TOPBAR_END`) get no render-hook scopes, and it re-renders
  on its own (`refresh-topbar`, user-menu actions), when no page is in memory.
- The panel accepts a replacement: `$panel->topbarLivewireComponent(Topbar::class)`.
- In SPA mode only the topbar's right end is `x-persist`ed (`topbar.end.panel-…`). The rest of the topbar and the
  layout render again on every `wire:navigate`, so the bar follows the new page.
- deptrac: module UserInterface may depend on `CoreUserInterface` (`app/Core/UserInterface/…`), **not** on
  `app/Filament` (Panel). So the contract pieces go to Core and the topbar component to `app/Filament`.

## Design

### Core: `app/Core/UserInterface/Filament/TopbarNavigation/`

```php
/** One bar: its owner page and its items. Subclassed once per bar, e.g. HistoryAndLogsTopbarNavigation. */
abstract class TopbarNavigation
{
    /** @return class-string<Page> the page whose sidebar item is active for every page of this bar */
    abstract public static function owner(): string;

    /** @return list<TopbarNavigationItem> in bar order */
    abstract public static function items(): array;

    /** aria-label of the <nav>; defaults to the owner's navigation label */
    public static function label(): string;

    /** @return list<TopbarNavigationItem> visible items, or [] when fewer than 2 are visible */
    final public static function visibleItems(): array;

    /** the active visible item for the given page class and request, or null */
    final public static function activeItem(string $pageClass, Request $request): ?TopbarNavigationItem;
}

/** One link of a bar. Immutable, fluent. */
final class TopbarNavigationItem
{
    public static function make(string $key): self;            // stable key, e.g. 'zadania'
    public function label(string|Closure $label): self;        // translated
    public function page(string $pageClass, array $parameters = []): self; // URL = $pageClass::getUrl($parameters)
    public function badge(int|string|Closure|null $badge): self;
    public function visible(bool|Closure $condition): self;    // ANDed with $pageClass::canAccess()
    public function activeWhen(Closure $condition): self;      // fn (string $pageClass, Request $request): bool
}

/** Implemented by every page that shows a bar. */
interface HasTopbarNavigation
{
    /** @return class-string<TopbarNavigation> */
    public static function getTopbarNavigation(): string;
}

trait InteractsWithTopbarNavigation
{
    // protected static string $topbarNavigation; read by getTopbarNavigation()
    // topbarTitle(string $pageTitle): string, "<active label> · <page title>" when the active item points at
    //   static::class (decision 4), else $pageTitle unchanged
}
```

- No request-scoped registry is needed: the topbar resolves the current page from the **route** of the full GET
  (Filament page routes have the page class as their action) and asks it for its bar. On SPA navigation that
  is again a full GET of the new page.
- Visibility and active state are computed once per page load; a topbar re-render reuses them (decision 3).

### Panel: `app/Filament/Topbar/`

- `Topbar extends Filament\Livewire\Topbar`, registered in `AdminPanelProvider` with `->topbarLivewireComponent()`.
  - In `mount()` it resolves the current page class from the route. When the class implements
    `HasTopbarNavigation`, it stores the rendered items (key, label, url, badge, active) in a `#[Locked]` public
    array property. Otherwise it stores `[]`.
  - It renders the bar through the `TOPBAR_LOGO_AFTER` hook or a thin override of the topbar view, whichever keeps
    the vendor view untouched (prefer the hook; if the hook cannot reach the component's state, override
    `render()` to wrap the vendor view and pass the items). Write down which one and why in the delivery log.
- Blade component `panel::topbar.navigation` (`app/Filament/resources/views/topbar/navigation.blade.php`) draws
  one bar. It is used twice: in the topbar (shown `lg+`) and at content start for mobile (decision 12). Content
  start uses `PanelsRenderHook::CONTENT_START` registered in `PanelRenderHooks`, shown below `lg`, with the same
  items resolved the same way. The style lives next to the theme's tabs CSS (`resources/css/admin/…`), BEM:
  `topbar-nav`, `topbar-nav__item`, `topbar-nav__item--active`, `topbar-nav__badge`, `topbar-nav--content`.
- Markup: `<nav aria-label="…"><ul>…<a href aria-current="page">`. Links, not a `tablist`. Links get
  `wire:navigate` only through the panel's SPA mode (Filament adds it), not by hand.
- `PanelNavigation::items()`: the owner page's sidebar item is also active while the current page implements
  `HasTopbarNavigation` and its bar's `owner()` is that page (decision 5).

### Pilot: "Historia i logi" (Audit module)

- `HistoryAndLogsTopbarNavigation` in `app/Modules/Audit/UserInterface/Filament/Pages/HistoryAndLogs/`:
  owner `HistoryAndLogs`, one item per `HistoryAndLogsTab` case: key = case value, label = `$tab->label()`,
  page `HistoryAndLogs` with `['zakladka' => $tab->value]`, `visible(fn () => $tab->isAllowed())`.
  Badge on "Nieudane zadania" = number of failed jobs (`ListFailedJobs`), hidden at 0.
- `HistoryAndLogs` implements `HasTopbarNavigation`, uses the trait. `getHeading()` returns the plain page title;
  `getTitle()` returns `$this->topbarTitle(…)`.
- Remove `<x-filament::tabs>` from `audit::history-and-logs` (decision 11: the tabs move).
- Behaviour must stay as it is: first allowed tab when the URL's tab is missing or forbidden, `#[Locked]`
  `activeTab`, `guardTab()` on every server entry, the Pobierz action on "Logi techniczne". One user with only
  `AuditLog` sees 1 item, so **no bar**, which is correct (decision 7).
- Nothing else in the navigation or content changes.

### SPA mode

- `->spa()` in `AdminPanelProvider`.
- Manual check list for the user (record the outcome in the delivery log, add `spaUrlExceptions()` only for what
  fails): login / logout; start and stop impersonation (banner shows and hides); "Historia i logi": switching
  items, Pobierz downloads the log file; editing and saving a Glass model; Styleguide incl. the font switcher;
  notifications bell; browser back / forward across the bar's items (the active item follows).

## Docs

- **D-49** in `.scratch/admin-ui/functional-spec.md`, short: the topbar navigation (decisions 1–13 in a few lines),
  with a pointer to this issue.
- `admin-ui-design` skill: a short section "Topbar navigation vs tabs under H1" (when which, max ~5 items, one bar
  class per group of pages, owner page, mobile placement), plus the `topbar-nav` component in the component list.
- Styleguide: the Styleguide page shows a demo bar (its own `StyleguideTopbarNavigation` with 3–4 items pointing at
  Styleguide tabs, one with a badge). Local only, like the page.

## Tests (test-first where it is logic)

- `TopbarNavigation`: visible items drop forbidden ones; fewer than 2 visible → `[]`; active item by page class +
  declared parameters, ignoring other query keys (`zakladka=logi&tableFilters[...]=…`); `activeWhen` override wins.
- `TopbarNavigationItem`: URL from page + parameters; badge closure; visibility = `canAccess()` AND `visible()`.
- Trait `topbarTitle()`: same-page item → `"Item · Page"`; no active item / other page → page title unchanged.
- `Topbar` Livewire: renders the bar for a page with a bar, renders nothing for a page without one, keeps the
  items after `refresh-topbar` (the locked property survives a re-render).
- "Historia i logi" feature tests: existing tests stay green; the bar shows the allowed items with the right active
  one per `?zakladka=`; user with `AuditLog` only → no bar; failed-jobs badge count; `<title>` contains
  `"Nieudane zadania · Historia i logi"`; sidebar item "Historia i logi" active.
- `PanelNavigation`: the owner's item is active on a page of its bar.
- Panel: `AdminPanelProviderTest` asserts SPA mode and the custom topbar component.

## Done when

- Everything above is built; the "Historia i logi" page looks and behaves as before apart from the tabs living in
  the topbar (desktop) or at the start of the content (mobile).
- D-49 and the skill section are written; the Styleguide shows the demo bar.
- `npm run build` and `composer check` (pint, deptrac, phpunit) pass, run as `laradock` in the container.
- The user has done the SPA check list and the visual check on desktop and phone width; their remarks go to a fix
  round (small polish inline, per the UI-polish workflow).

## Out of scope

- Any other page getting a bar (Oferty / OLX, Glass model view, …): separate tickets.
- Changing Filament's sub-navigation, record sub-navigation or clusters.
- A "Więcej ▾" overflow menu; polling badges.

## Comments

- 2026-11-15: idea from the user, analysis and grilling in the same session. Rejected issue 12 (topbar back action,
  quick-access hub) used the same area; this bar is a different thing and keeps the D-44 ornament.
