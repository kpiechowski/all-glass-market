# 03 - Sign-in, panel access, rate limit, sessions

Status: resolved
Blocked by: 02

Spec: FR-07..FR-10, FR-12, RULE-13, WF-01 (without the deactivated and forced-change branches), §11 first edge case. Decisions: D-10, D-20, D-34, D-37, D-47.

## What

- **Panel access (FR-07):** only a User whose Role is registered (ticket 01) may enter the panel. Anyone else is refused at sign-in with the message from FR-07. The deactivated branch is ticket 07.
- **Sign-in (FR-08):** e-mail and password. `/` and the plain sign-in address lead to the panel sign-in page, which is noindex.
- **Rate limit (FR-09, RULE-13):** at most 5 failed attempts per minute per e-mail and IP, with a retry-time message.
- **Sessions (FR-10):** 120 minutes of inactivity, they survive closing the browser, and a password change ends the User's other sessions.
- **No self-registration (FR-12):** no registration route exists. Drop any leftover Fortify/Jetstream-style self-service if present (§14).

## Done when

- WF-01 is covered by feature tests: success, wrong password, rate limit hit, unregistered Role refused.
- A password change ends the User's other sessions, and this is tested.
- `composer check` passes.

## Comments

### Worker — 2026-11-01
- **Built:** FR-07 panel access via `RoleRegistry::isRegistered()` in a custom `Login` page (`UserInterface/Filament/Auth/Login`) and an `AuthenticatePanel` middleware (also refuses live sessions whose Role went away). FR-08 `/` and `/login` redirect to the panel sign-in; sign-in page carries a noindex meta (all panel pages, via render hook). FR-09/RULE-13 5 failed attempts per minute per e-mail+IP with retry-seconds message (wrong credentials count; a correct login clears). FR-10 session config already 120 min / no expire-on-close (asserted in a test); a password change ends other sessions through the existing `AuthenticateSession` hash check, and `KeepsOwnSessionAfterPasswordChange` lets the changing session adopt the new hash (EditProfile, EditUser). Tested for ChangeUserPassword (self and by Admin) and UpdateUser with password. FR-12 no registration route (tested). WF-01 tests: success, wrong password, unknown e-mail, rate limit, unregistered Role refused.
- **Deviations:** `UserModel` no longer implements `FilamentUser` (Infrastructure may not use Filament nor ModuleApi); the Filament-side check replaces `canAccessPanel`, which removed the FilamentUser/Panel baseline lines. Removed boilerplate `tests/Feature/ExampleTest.php` (expected `/` = 200). Rate-limit message is a validation error on the e-mail field, not a notification. The unregistered-Role refusal does not count against the limit. Remember-me cookie of the changing session is not re-issued after a password change.
- **Seams left:** deactivated branch and forced password change (ticket 07/WF-07); password reset link (ticket 04, Filament reset pages still check `FilamentUser`, now absent, so they allow).
- **Open questions:** none
- **Check:** composer check green (186 tests)

### Review — 2026-11-01
- **Verdict:** approved after 0 fix rounds
- **Notes:** Panel access is decided by `AuthenticatePanel` (replaces Filament's `Authenticate`, so the model no longer needs `FilamentUser`) and the custom `Login`, both via `RoleRegistry::isRegistered()`. Accepted: a correct password with an unregistered Role does not count against the rate limit; the changing session's remember-me cookie is not re-issued. Filament's reset pages skip their `FilamentUser` check now — ticket 06 decides who may reset. `KeepsOwnSessionAfterPasswordChange` must be called by any new UI path that changes the signed-in User's password (06).
