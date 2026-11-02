# 12 - Polish translations for framework and leftover English strings

Status: resolved
Blocked by: none

Spec: RULE-01..RULE-03 (messages the user reads), golden rule 6 in `CLAUDE.md`. Source: the browser QA of 2026-11-01.

## What

1. **Laravel's own messages in Polish.** Validation messages show raw keys (`validation.unique`, `validation.required`, `validation.email`, `validation.min.string`, `validation.same`), because the app has no `lang/` directory while `APP_LOCALE`/`APP_FALLBACK_LOCALE` are `pl`.
   - Run `dartisan lang:publish` and translate `lang/pl/validation.php`, `auth.php`, `passwords.php` and `pagination.php` into Polish, keeping every key and `:placeholder`.
   - Keep `lang/en/` as published.
   - Add attribute names used by the Users forms (`email`, `name`, `password`, `phone`, `company_nip`…) under `attributes` where Filament does not already supply the field label.
2. **The reset and invitation e-mails** carry English boilerplate: "Regards,", "If you're having trouble clicking…", "All rights reserved.". Translate them through `lang/pl.json` (the keys are the English strings of Laravel's mail templates).
3. **"Generate password"** (tooltip and aria-label of the password generator in "Mój profil") comes from a module translation key.
4. **"Utwórz Użytkownik"** on the create page: make the title read "Utwórz użytkownika" through the Users translation files (the resource's model label or the page title).

Touch translation files and the strings' call sites only. No behaviour changes, no layout or colour changes.

## Done when

- A test asserts a Polish validation message on the create-user form (for example the duplicate e-mail), and that the create page title is "Utwórz użytkownika".
- No raw `validation.*` key appears for the Users forms.
- `composer check` passes.

## Comments

### Worker — 2026-11-02
- **Built:** RULE-01 to RULE-03 messages in Polish: `lang/pl/validation.php`, `auth.php`, `passwords.php`, `pagination.php` (published from `lang/en/` by `lang:publish`, which stays as published) and `lang/pl.json` for the Laravel mail boilerplate ("Pozdrawiamy,", the "trouble clicking" line, "Wszelkie prawa zastrzeżone."). `validation.attributes` is left empty because Filament already supplies every Users form field label as the attribute name. The "Generate password" suffix action in "Mój profil" takes its label from `users::profile.actions.generate_password` (`PasswordForm.php`). The create page title is `users::resources.user.titles.create` = "Utwórz użytkownika", returned by `CreateUser::getTitle()`; the model label stays "Użytkownik". Tests: `CreateUserTest` (duplicate e-mail shows "Ta wartość w polu adres e-mail jest już zajęta.", no `validation.unique`; title is "Utwórz użytkownika"), new `PasswordLinkNotificationTest` (reset mail boilerplate is Polish).
- **Deviations:**
  - `dartisan` is not installed in the workspace container (`dartisan: command not found`), so Artisan ran as `php artisan` through `run.sh`. Same commands, nothing else changed.
  - The create title is set by overriding `getTitle()` on the page, not by changing the model label. The model label feeds every other heading and Filament's title-cased "Utwórz :label" would still read "Użytkownik" with the label alone.
  - `validation.attributes` is empty: Filament supplies the labels, so no extra attribute names were needed (the issue asks only where Filament does not).
  - Added `PasswordLinkNotificationTest`, which is not in the issue's Done-when list, to prove the JSON keys match the mail output.
  - The Filament validation attribute is lowercased ("adres e-mail") in messages; the test asserts that form.
- **Seams left:** none. Ticket 11 touches the same Users UI files only through its own changes; this ticket changed one line in `PasswordForm.php` and the `CreateUser` page title method.
- **Open questions:**
  - The admin `UserForm` password generator (`Actions generate-password`, around line 159) still shows the English Filament-derived label "Generate Password" as tooltip and aria-label. The issue names only "Mój profil", so it is unchanged. A key such as `users::resources.user.actions.generate_password` plus a `->label()` call would fix it; say if you want it in this ticket.
  - `lang/pl/validation.php` custom example (`attribute-name.rule-name`) is translated as an unused placeholder. Harmless, but it can be left as published if preferred.
- **Check:** composer check green (pint, deptrac 0 violations, test presence, phpunit 421 passed).

### Worker — fix 1
- **Built:** the admin form's password generator (`UserForm.php`, `generate-password` suffix action) now takes its label from `users::resources.user.actions.generate_password` = "Wygeneruj hasło" (added in `resources.php`), so the tooltip and aria-label are Polish. Covered by `CreateUserTest::test_the_password_generator_is_labelled_in_polish`. This closes the open question from the first entry.
- **Deviations:** none. Diff to `UserForm.php` is the one `->label(...)` line.
- **Seams left:** none.
- **Open questions:** none.
- **Check:** composer check green (pint, deptrac 0 violations, test presence, phpunit 422 passed).

### Review — 2026-11-02
- **Verdict:** approved after 1 fix round
- **Notes:** Round 1 translated the admin form's generate-password action too. `lang/pl` holds Laravel's validation, auth, passwords and pagination messages; `lang/pl.json` the mail boilerplate. The create title is a `getTitle()` override; the model label is unchanged.
