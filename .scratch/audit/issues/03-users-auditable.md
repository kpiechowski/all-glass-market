# 03 - Users registers the User as auditable

Status: resolved
Blocked by: 02

Spec: FR-05, FR-07, FR-11, §9 Users. Users spec §9 "Audit" (amended 2026-11-02). Decisions: D-17, D-21, D-23, D-33.

## What

- Users' persistence emits `RecordChanged` for the User through an observer. The User model's adapter uses the Core relation-sync helper for anything relation-like it wants audited. Extra Permissions are a JSON list, so present them as a list field with added/removed values.
- Users registers the User type with Audit:
  - **Name:** "Użytkownik".
  - **Title:** the User's name.
  - **Link:** the user detail.
  - **Labels:** Polish.
  - **Excluded:**
    - password, remember token, any 2FA secrets;
    - invitation and reset tokens;
    - pure timestamps that are not business data.

    Sensitive fields are simply not registered (D-17).
  - **Audited:**
    - contact and company data;
    - Role;
    - Account type and declaration date;
    - deactivation state;
    - Extra Permissions;
    - the "password change required" marker (D-23).
  - **Presenters:** Role and Account type by label, Extra Permissions by their translated names.
  - **Restorable fields:** none for now.
- The registration is wired in Users' module provider. Nothing in Audit names Users.

## Done when

- Tests show that creating, editing, deactivating and reactivating a User, changing their Account type, granting or revoking an Extra Permission, and setting or clearing the marker each produce the right Change entry with the right Actor.
- A password change produces no field value.
- `composer check` passes.

## Comments

### Worker — 2026-11-05
- **Built:** Users registers `users.user` with Audit in `UsersModuleServiceProvider` (name "Użytkownik", title = name, link = user detail via `UserUrls`, Polish labels in `users::audit`, Role and Account type as enum presenters, dates as date presenters; excluded: password, remember token, 2FA secrets, invitation/reset tokens, `email_verified_at`, `account_type_changed_at`; restorable none) (FR-05, FR-07, FR-11, D-17, D-23, D-33). `UserObserver` (registered in `UsersEventServiceProvider`) feeds `ChangeRecorder`. Extra Permissions (a JSON column) are reported by `EloquentUserRepository` add/save as a relation change `extra_permissions` with added/removed keys, shown by a new Audit presenter `ValuePresenter::translated('users::permissions.')`. Actor audit of every write path: all Users commands already carry `Actor`; guest flows use `Actor::system('guest password reset request' / 'password link redeemed by guest')`, console Super Admin commands `Actor::system('console: users:...')`; `dev:create-user` now runs under `Actor::system('console: dev:create-user')` (was an unguarded write). Tests: `UserAuditTest` (create, edit, deactivate, reactivate, account type, grant/revoke, marker, admin password change leaves no password value, guest redemption, dev command, remember token).
- **Deviations:** (1) The observer skips an update whose only dirty columns are `remember_token`/`updated_at`: the session guard writes them at sign-in/out with "remember me", outside any command; the data is never audited, so skipping is not a fallback Actor. Sign-in has no last-login column in V2. (2) Added `ValuePresenterKind::Translated` to Audit (small ModuleApi extension) so Extra Permissions show by translated name; the lookup mirrors `RoleDisplay::permissionLabel` (other modules' permissions fall back to the raw key until their prefix is known). (3) Registration sits in the provider, not an Infrastructure class: deptrac forbids Infrastructure depending on Application/ModuleApi of another module. (4) `ChangeModelFactory` creates its actor user with `withoutEvents` and two existing tests were adjusted (`RecordChangeCommandHandlerTest` filters by type; `EloquentUserRepositoryTest` runs inside an Actor frame) because User writes now need an Actor. (5) Raw old values: `getOriginal()` returns cast values (enum/Carbon) while new values are raw strings; entries look right in tests (enum old shows `private`), Carbon old dates were not checked beyond null/not-null.
- **Seams left:** user-detail history tab (04/05); restorable fields (06).
- **Open questions:** guest invitation acceptance and password reset share one System reason; split if the history should tell them apart.
- **Check:** composer check green

### Worker — fix 1
- **Built:** (1) `ChangeRecorder` now records raw values on both sides: `old` from `getRawOriginal()`, and booleans written as 0/1 on both sides (the driver returns 0/1 while a freshly set attribute can be a PHP bool, so the shapes differed). Tests: Core `ChangeRecorderTest` with a datetime-cast field; User `deactivated_at` shape and reactivation `old` equals the earlier `new`; account type old/new are raw strings. To show the raw 0/1 flag I added `ValuePresenter::boolean()` ("tak"/"nie") to Audit and used it for `password_change_required`. (2) System reasons are translation keys: `ChangePresenter::actorReason()` translates a key and leaves plain text as written (tested). Users reasons live in `users::actors` (password reset requested, password reset redeemed, invitation accepted, console grant/revoke Super Admin); `AcceptInvitation` and reset now use different reasons; `dev:create-user` uses `core::common.actors.dev_create_user`. `WriteAccountTypeChangeJournalEntries` writes Journal entries, not User changes, so its reason never reaches a Change entry and was left as is.
- **Deviations:** none new beyond `ValuePresenter::boolean()` (needed because of the 0/1 normalisation).
- **Seams left:** ticket 04 shows "System: " + `ChangePresenter::actorReason($entry->actor_reason)`.
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-05
- **Verdict:** approved after 1 fix round
- **Notes:** Core fix: `ChangeRecorder` records raw old and new values (`getRawOriginal`, booleans as 0/1), so entries hold what Restore writes back. System reasons are translation keys resolved at display by `ChangePresenter::actorReason()` (plain text passes through); Users reasons live in `users::actors`, with invitation acceptance and password reset split. Accepted additions to Audit's ModuleApi: `ValuePresenter::translated()` and `::boolean()`. The User observer skips remember_token/updated_at-only saves (session guard). Registration is in `UsersModuleServiceProvider` (deptrac). Seam for 04: "System: " + `ChangePresenter::actorReason()`.
