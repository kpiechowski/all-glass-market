# 06 - Password reset, invitations, forced password change

Status: resolved
Blocked by: 03

Spec: FR-11, FR-24 (invitation path), FR-25 (password sets the marker), FR-26, FR-36..FR-39, RULE-14, RULE-15, WF-03, WF-04 (invitation), WF-07, §7 Password change required, §10 E-mail, §11 password cases. Decisions: D-34, D-38, D-40, D-41, D-46, D-47.

## What

- **Reset (FR-11, WF-03, RULE-14):**
  - "Nie pamiętasz hasła?" on the sign-in page.
  - The link is single-use and valid 60 minutes. Unknown and deactivated e-mails get the same neutral response; the deactivated case arrives with ticket 07, so leave a seam.
  - Setting the password signs the User in and ends their other sessions.
- **Invitation (FR-24, WF-04):**
  - a single-use link valid 7 days;
  - offered only while e-mail sending works (§10). Decide how "works" is detected, and note it.
- **Admin actions (FR-26):** re-send an invitation or reset link, set or clear "password change required" without changing the password. All within RULE-08.
- **Forced change (FR-36..FR-39, WF-07):**
  - **Set by:** an Admin setting a temporary password or changing a password; an Admin can also set or clear it by hand.
  - **Cleared by:** any password the User sets.
  - **While set:** every panel page leads to "Ustaw nowe hasło".
  - **New password:** RULE-03, differs from the current one, the current one is not asked for.
  - **Afterwards:** other sessions end, the change is audited as forced, and the User lands on the dashboard.
  - **Markers:** "wymaga zmiany hasła" in the list and detail, plus a list filter.
  - **Impersonation:** an impersonator is not redirected. Impersonation arrives with 08, so leave the seam.

## Done when

- WF-03, WF-04 (invitation) and WF-07 are tested, including the expired or used link and the same-password refusal.
- `composer check` passes.

## Comments

### Worker — 2026-11-01
- **Built:** Every password write is a command now. `User` entity owns the marker (`setPasswordByAdmin` sets it, `setOwnPassword` clears it, `requirePasswordChange` / `clearPasswordChangeRequirement`, `completeForcedPasswordChange` refuses a repeated password; RULE-15, FR-36). Column `users.password_change_required`, mapper, factory state.
  - **Reset (FR-11, WF-03, RULE-14):** panel `->passwordReset()` with custom pages. `RequestPasswordReset` page sends `RequestPasswordResetCommand` (system actor) and always answers the same; the handler sends nothing for unknown e-mail, an ineligible User, or when e-mail is down. `ResetPassword` page sends `SetPasswordFromLinkCommand`: the handler redeems the single-use token (port `PasswordLinks`, adapter `LaravelPasswordLinks` over Laravel brokers: `users` 60 min, `invitations` 7 days, own table `invitation_tokens`), checks eligibility, sets the password, clears the marker, emits `PasswordChanged`; the page then signs the User in. Other sessions end through the changed password hash (FR-10). Expired, used, voided (a re-send replaces the token) and wrong-kind links are refused with a message pointing to a new request.
  - **Who may reset:** `PasswordLinkEligibility` = Role is registered (setting a password signs in, and AuthenticatePanel would refuse otherwise). Used by the request, redeem and admin-send handlers. Filament's own pages (which saved the model and skipped the FilamentUser check) are replaced.
  - **Invitation (FR-24, WF-04):** `CreateUserCommand` gained `sendInvitation` (password null, unusable random hash, link sent in the same transaction; a send failure rolls the creation back). Create form shows a Radio (invitation / temporary password) only while e-mail works; temporary password sets the marker. Signed route `filament.admin.auth.invitation` -> `AcceptInvitation` page.
  - **"E-mail works" detection:** `EmailSending` ModuleApi = default mailer is a real transport (not `log`/`array`). Production has none today, so the option is hidden and links are refused/neutral there. Tests switch `mail.default` to `smtp` with `Notification::fake()`.
  - **Admin actions (FR-26):** `SendPasswordLinkCommand` (invitation or reset), `SetPasswordChangeRequirementCommand` (set/clear); both authorize ManageUsers and RULE-08 via `UserManagementGuard`. Actions group on the detail and edit pages. `ChangeUserPassword` and `UpdateUser` set the marker when an Admin sets another User's password; a User's own password clears it.
  - **Forced change (FR-36..39, WF-07):** `RequirePasswordChange` middleware (after `AuthenticatePanel`) redirects every panel page except the screen and logout to `SetNewPassword` ("Ustaw nowe hasło"); `CompleteForcedPasswordChangeCommand` (self only, RULE-03, must differ from the current hash, no current password asked) clears the marker, emits `PasswordChanged(reason: forced)`; the page keeps its own session via `KeepsOwnSessionAfterPasswordChange` and lands on the dashboard; other sessions end. "wymaga zmiany hasła" badge in the list and detail, list filter.
  - **`UserModel::save()` exception narrowed** to `remember_token` (+ `updated_at`): the `password` key is gone (tested: direct password write throws). What remains is the session guard's remember-token write on sign-in/out; Laravel's guard has no hook to route that through a command. Rehash-on-login is not called anywhere (Login validates credentials itself).
- **Deviations:** (1) Login untouched: the forced-change redirect is done by the middleware, which also covers "any panel page". (2) `SetNewPassword`, `AcceptInvitation` routes are registered in `AdminPanelProvider` (`routes()` / `authenticatedRoutes()`) because Filament's `SimplePage` has no route registration. (3) Infrastructure builds the signed URLs from the route names `filament.admin.auth.*` (deptrac forbids Infrastructure to use the UI), which ties the adapter to panel id `admin`. (4) The reset request page shows the neutral answer even while e-mail is down (spec §10 asks only that the invitation option be hidden); the "Nie pamiętasz hasła?" link stays. (5) A failed send during admin re-send or create is reported to the Admin as a refusal and nothing is created (create) / token removed (re-send); the Guest path swallows it. (6) `PasswordChanged` event emitted for every password path (own, by_admin, forced, reset, invitation); no Audit listener yet. (7) Test-first order followed for handlers, but not each test was seen red separately.
- **Seams left:** Deactivated: add the check to `PasswordLinkEligibility::allows()` (ticket 07) and nothing else changes in the neutral request. Impersonation: `RequirePasswordChange::isImpersonating()` returns false until ticket 08. Audit: listener for `PasswordChanged` (reason `forced` for FR-38). Remember-me cookie is not reissued after a password change (as 03).
- **Open questions:** none
- **Check:** composer check green (341 tests)

### Worker — fix 1
- **Built:** Rebased onto `feat/users-01-05` (07 and 08 merged), resolving 20 conflicts by keeping both sides: entity/mapper/model/factory carry `deactivatedAt`, `extraPermissions` and the password-change marker; `CreateUser` takes both `extraPermissions` and `sendInvitation`; resources, lang files, panel provider and `UserTest` keep both tickets' parts. 07's seam: `PasswordLinkEligibility::allows()` also refuses a Deactivated User, so the reset request stays neutral (nothing sent), an admin "send link" is refused (`password_link_not_allowed`), and a link issued before deactivation cannot be redeemed. 08's seam: `RequirePasswordChange::isImpersonating()` is now `ImpersonationSession::isActive()` (WF-06, WF-07). Tests added for each.
- **Deviations:** none
- **Seams left:** Audit listener for `PasswordChanged`.
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-01
- **Verdict:** approved after 1 fix round
- **Notes:** Round 1 was the rebase onto 07 and 08 plus their seams (Deactivated Users get no links and cannot redeem old ones; no forced-change redirect while impersonating). Every password write is a command; `UserModel::save()` now lets through only `remember_token`. Rehash-on-login never fires because `Login` validates credentials without `attempt()` — V1 hashes are not upgraded on sign-in. Invitations are hidden while the default mailer is `log`/`array` (`EmailSending`), so production offers only the temporary-password path until a real mailer is configured. Signed URLs use `filament.admin.auth.*` route names (tied to panel id `admin`). The 06 and 07 migrations share the `2026_11_01_400000` prefix; filename order keeps them deterministic. Audit listener for `PasswordChanged` is left to the Audit rework.
