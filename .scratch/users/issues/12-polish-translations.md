# 12 - Polish translations for framework and leftover English strings

Status: ready-for-agent
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
