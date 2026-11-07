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
