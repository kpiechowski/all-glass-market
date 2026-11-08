# 11 - Retrofit Users and Audit from the skill

Status: resolved
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

## Comments

### 2026-11-07 (agent, round 1 Users)

Users rebuilt from `admin-ui-design` + `filament-module-wiring` only: list (A), create/edit (B), view (C), "Mój profil" (D), action modals (E). Audit untouched apart from one Users-view test in `RecordHistoryTest` (the history is now a lazy tab). `composer check` green.

Skill gaps (skill fixed first, then code):

1. **Record tabs can't take `tabs--underline`.** C and variants said to set it on record tabs, but Filament builds them (`hasCombinedRelationManagerTabsWithContent()`). Fix: C + variants now say Filament-built list and record tabs take the look from the theme; the class goes only on Tabs you build.
2. **Lone "Szczegóły" tab.** With `return true`, a user without the audit permission gets a single tab. Fix: C skeleton returns `$this->getCachedRelationManagers() !== []`.
3. **Identity header without an image.** The skeleton assumes an avatar; Users have none, and the phone had no place. Fix: C says no image means name + main contact only, no placeholder; a second contact sits under the main one.
4. **Indicator next to the status in an infolist.** C said "an indicator next to the status" without saying how. Fix: C says the indicator is a second badge in the status entry (`->state()` list, `->color()` per item).
5. **Date format.** Skeletons used bare `->date()`, which renders "lis 5, 2026" in Polish. Fix: SKILL Copy and the A/C skeletons use `d.m.Y` / `d.m.Y H:i`; the checklist asks for an explicit format.
6. **List tabs: default, "Wszystkie", counts.** A was silent. Fix: A says the first tab is the working set; "Wszystkie" comes last when people search across the split; tabs have no count unless it is cheap.
7. **Exception states on the card.** A was unclear on `status()` vs `flags()` for inactive / password change. Fix: A says a state marking only the exception is a flag; `status()` is for a state every row has.
8. **Edit as a row action.** A said only "View is not a row action". Fix: A says Edit may stay in the row "⋮".
9. **Action colours.** The rule banned only warning/info buttons, so green Reactivate, amber Impersonate and amber "Zmień hasło" went through. Fix: SKILL says actions are primary, gray or danger only.
10. **Choosing secondaries.** "≤2 secondary" didn't say which ones. Fix: SKILL defines a secondary as a harmless, frequent action on the record; anything else goes into "⋮".
11. **Grouping inside "⋮".** Not covered. Fix: SKILL uses a nested `ActionGroup::make([…])->dropdown(false)` as a divided block (here the password block).
12. **Confirmation submit label.** E said "never Potwierdź", but Filament's default is "Potwierdź", and four Users actions inherited it. Fix: SKILL says every confirmation sets `modalSubmitActionLabel()`.
13. **`->aside()` sections.** The profile used them and the skill was silent. Fix: SKILL says sections stack full width, never `->aside()`.
14. **Sidebar indicator mechanics.** D said "! or a warning icon with a tooltip" without saying how. Fix: SKILL names `getNavigationBadge()` `'!'` + `getNavigationBadgeColor()` `'warning'` + `getNavigationBadgeTooltip()`.

Open, not fixed (needs the user):

15. **Empty state on a tabbed list.** `TableEmptyState` knows search and filters, not tabs. An empty "Nieaktywni" tab shows "Brak użytkowników" + "Utwórz użytkownika". Proposal: treat a non-default tab as narrowed in `TableEmptyState` (Core), with a "Brak rekordów w tej zakładce" message. A skill rule follows once agreed.
16. **Initials in the identity header.** The list's person cell shows initials, the record header shows none (gap 3). Should the header show an initials circle for records without an image? That needs a small Core `ViewEntry`.

D-24 naming proposals (not applied): "Dziennik" (Users journal, events for the user) → **"Powiadomienia"** or **"Zdarzenia"**; "Dziennik audytu" → **"Historia zmian"** (same as the record tab); "Logi techniczne" → keep, or **"Log techniczny"**.
- 2026-11-07 (manager): round 1 (Users) accepted by the user at about 90 %; the user fixes the remaining small issues themselves. Open questions from round 1 left to the user: D-24 naming (agent proposed "Dziennik" → "Zdarzenia"/"Powiadomienia", "Dziennik audytu" → "Historia zmian"), the empty-tab state (gap 15), initials without an avatar (gap 16). Round 2 (Audit) not started; work moves to issue 13 on a new branch.
- 2026-11-08 (manager): D-24 settled with the user as D-24a: one Audit page "Historia i logi" with tabs Historia zmian / Logi techniczne / Nieudane zadania; Users "Dziennik" keeps its name. Round 2 (Audit) starts on `feat/audit`.

### 2026-11-08 (agent, round 2 Audit)

Audit rebuilt from `admin-ui-design` + `filament-module-wiring` only. The two pages are now one page, "Historia i logi" (D-24a), at `/admin/history-and-logs?zakladka=historia|logi|zadania`. Each tab is template A; the record history tab is A inside C. `composer check` is green.

Skill gaps (skill fixed first, then code):

1. **Page tabs on a standalone page.** The skill had tabs only for resources (`getTabs()`) and record views. It said nothing on how to build them, where the tab lives in the URL, per-tab permissions or the default tab. Fix: A "Page tabs on a standalone page": `x-filament::tabs` links, `#[Locked] #[Url(as: 'zakladka')]`, forbidden tabs hidden, the page opens on the first allowed tab, every server entry re-checked with `abort_unless`.
2. **`tabs--underline` on Blade tabs.** variants.md said to set the class on page tabs you build. On `<x-filament::tabs>` the class lands on `.fi-tabs` itself, so it never matches `.tabs--underline > .fi-tabs`. Fix: variants.md says a Blade tab bar takes the look by itself and carries no class.
3. **Header action of one tab.** "Pobierz plik" belongs to the logs tab only. Fix: A, page tabs: the header shows only the active tab's actions.
4. **Rows with no page of their own.** A says the row click is `recordUrl` and View is not a row action. A change or a log line has only a details modal. Fix: A, Actions: the row opens the details modal by click; the modal action sits on the first column (`->action()`) and `->recordAction()` names it, so it never shows as a row action.
5. **Read-only details modal.** E covered forms and confirmations only. Fix: E gained a "read-only details" skeleton: no submit, "Zamknij", our own Blade body.
6. **Date and time on event lists.** A allowed only `date('d.m.Y')` or `since()`. A history needs the time. Fix: A allows `->dateTime('d.m.Y H:i')` on event lists.
7. **Technical tables (D-12 exception).** A required a `RecordCardColumn` on every list and had no mobile rule for logs. Fix: A "Technical table": no card, no `visibleFrom('md')`, Filament's scrolling table on mobile with ≤4 short columns and the long text last. Seconds are allowed when a filter fixes the day.
8. **A selection the table needs (channel, day).** It had been a form above the table, and the skill was silent. As ordinary filters, channel and day would always count as active, so the empty state would always read "Brak wyników dla tych filtrów". Fix: A says such a selection is an inline `Filter` with a default and `indicateUsing(fn (): array => [])`, never a form above the table.
9. **A bare `->badge()` is primary.** ChangeKind is a category, but it rendered primary because the column had no enum colour. Fix: A says a badge without an enum colour sets `->color('gray')`.
10. **`info` and `primary` on badges.** The skill named only success/warning/danger. LogLevel coloured info and notice `info`. Fix: SKILL Colour = state says a state that needs no attention is `gray`, and `info`/`primary` colour no badge. LogLevel was corrected. Not touched (out of scope): Users `JournalSeverity::Info` is still `primary`.
11. **An action that overwrites values (Przywróć).** It was `warning`, which the skill bans. Danger is for dangerous menu items only, and Restore deletes nothing. Fix: SKILL Action hierarchy says such an action is grey, may stand in the row as an icon, and only its confirmation button is `danger`.
12. **Relation manager tables.** It was unclear whether a record tab's table follows A. Fix: C says it does: card, columns, inline filters, row click, empty state.

Code per screen:
- Historia zmian: Kiedy · Rekord (link + type below) · Rodzaj (grey badge, restore marker below) · Autor zmiany, plus a mobile card. The row opens the details. Przywróć is a grey icon with a tooltip; the confirmation names the act ("Przywróć wartości") and its button is red. Five filters go in the funnel, frequent first, with chips for record id and period. Own empty state.
- Logi techniczne: Kanał / Dzień / Poziom as three inline filters (the old form above the table is gone), plus search. Czas (H:i:s) · Poziom (Polish labels; grey unless warning or worse) · Komunikat. The row opens the details. "Pobierz plik" is grey and shows only on this tab. Own empty state.
- Nieudane zadania: Kiedy (d.m.Y H:i) · Zadanie · Błąd. The row opens the details. Ponów and Usuń sit in the row "⋮", Usuń red. The Usuń confirmation reads "Usuń zadanie" (it was Filament's "Potwierdź"). Notifications are shorter. Own empty state.
- Record history tab (record view, lazy, second tab): Kiedy · Rodzaj · Autor zmiany plus a card. Its two filters sit inline. Row click, the Przywróć icon and the empty state work as on the central tab.
- Sidebar: one item "Historia i logi". Quick access: `audit.history_and_logs` replaces `audit.log` + `audit.technical_log`; old pins drop silently as unknown keys.
- 2026-11-08 (manager, closing): round 2 (Audit) accepted. User decisions: log levels simplified back to the PSR names (debug, info, warning…), seconds stay in technical logs, Users `JournalSeverity::Info` (still primary) left for later. Open from round 1: empty-tab state (gap 15), initials without an avatar (gap 16).
