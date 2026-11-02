# 11 - QA fixes from the panel click-through

Status: resolved
Blocked by: none

Spec: FR-13, FR-23, FR-25, FR-29, FR-32, WF-01, WF-07, RULE-04, §11. Source: the browser QA of 2026-11-01 (screenshots in the session scratchpad `qa/`, names quoted below).

Out of scope, decided by the user: audit wiring, colours and layout (the invisible "Użytkownik" Role badge, table overflow at 1400px), and translations (ticket 12).

## What

1. **"Edytuj profil" in the user menu (FR-13).** The avatar menu shows only the name and "Wyloguj się" (`s3-01-usermenu.png`). Add the "Edytuj profil" item leading to "Mój profil".
2. **No "Edytuj" on rows the actor cannot edit (FR-25, §11).** An Admin sees "Edytuj" on a Super Admin's row and gets a bare 403 (`s4-01-list.png`). Gate the table's edit action by the same rule the detail page already uses (`UserManagementPolicy` through `CurrentActor`).
3. **Journal line breaks (FR-29).** Plain-text content with `\n` renders on one line (`s6d-01-html-entry.png`). Keep it escaped and show the line breaks, in the entry details and the notification body.
4. **User detail completeness (FR-23).** Show the Seller declaration acceptance date and version for a Company User, and the active/inactive state for every User, not only "konto nieaktywne".
5. **Refusals as field errors.**
   - Company without NIP or company address (RULE-04) arrives as a toast. Show it on the NIP and address fields.
   - The same password on "Ustaw nowe hasło" (WF-07) arrives as a toast. Show it on the password field.
6. **Sign-in link text (WF-01).** The link reads "Nie pamiętam hasła"; the spec says "Nie pamiętasz hasła?".
7. **Account-type lock time (FR-19).** "Zmiana rodzaju konta jest zablokowana do: HH:MM" shows UTC. Show it in `Europe/Warsaw` without changing `app.timezone`.
8. **Stale notification after deleting its entry (FR-32).** Deleting a Journal entry leaves its panel notification, whose "Zobacz wpis" leads to a bare 404 (`s6e-02-stale-notif.png`). Delete the entry's notifications with it.
9. **Account-type entry content (FR-21).** The `users` Journal entry repeats its title as content. Give it content of its own: who, from which type to which, when.

## Done when

- Each point has a test (Livewire or feature) that fails before the fix.
- `composer check` passes.

## Comments

### Worker — 2026-11-02
- **Built:** (1) FR-13 "Edytuj profil" user-menu item to "Mój profil" (panel provider). (2) FR-25 table "Edytuj" gated by `UserResource::canEdit`. (3) FR-29 line breaks via `nl2br(e())` in the entry infolist and in `FilamentPanelNotifier` body. (4) FR-23 declaration date/version for Company; active/inactive state for every User. (5) RULE-04 refusal shown on NIP and address fields (admin edit page and "Mój profil"); WF-07 same-password refusal on the password field, via a new optional `$fieldErrors` map on `CommandSender::send()`. (6) WF-01 link reads "Nie pamiętasz hasła?" (Login overrides the password component hint). (7) FR-19 lock time in Europe/Warsaw (profile helper text and the refusal message). (8) FR-32 `PanelNotifier::retract($url)` added; the delete handler retracts the entry's notification. (9) FR-21 `users` entry content "X zmienił rodzaj konta z: A na: B (dd.mm.yyyy HH:MM, Warsaw)".
- **Deviations:** `AccountTypeChanged` gained `previousAccountType` and `changedAt` (needed for "from" and "when"). `PanelNotifier` contract gained `retract()`; notifications are matched by the JSON-encoded entry URL because the notifications table has no subject reference. Existing tests asserting UTC lock times (10:30, 10:20) now expect Warsaw times. The old toast assertion for the same-password refusal was replaced by the field-error test. New keys added to module lang files (`profile.user_menu_label`, `login.forgot_password`, `notifications.account_type_changed.content`, `resources.user.fields.declaration_*`) - ticket 12 may touch the same files.
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check green

### Worker — fix 1
- `FilamentPanelNotifier::retract()` no longer relies on LIKE escaping: SQL narrows candidates by the URL's last path segment (over-matching is harmless), PHP decodes `data` and deletes only rows with an action whose url equals the given one. Test covers `/`, `_`, `/1` vs `/10` vs `/1/edit`. Check: composer check green.

### Review — 2026-11-02
- **Verdict:** approved after 1 fix round
- **Notes:** Round 1 replaced `retract()`'s LIKE on JSON-escaped URLs (never matches on MySQL, where `\` escapes in LIKE; tests run on SQLite) with a narrowing LIKE plus an exact PHP check. Accepted: `AccountTypeChanged` carries `previousAccountType` and `changedAt`; `CommandSender::send()` takes an optional refusal → form-field map; `PanelNotifier` gained `retract(url)`. Lock times display in Europe/Warsaw; `app.timezone` stays UTC.
