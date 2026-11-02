# 08 - Extra Permissions and impersonation

Status: resolved
Blocked by: 03

Spec: FR-06, FR-28, RULE-10, RULE-11, WF-06, §11 impersonation cases. Decisions: D-21, D-22, D-47.

## What

- **Extra Permissions (FR-06, RULE-11):**
  - a Super Admin grants and revokes any registered Permission to an individual User in the create and edit form;
  - only Super Admins see the field, and the detail lists them separately for Super Admins;
  - grants and revocations are audited;
  - the Authorizer (ticket 01) also passes on an Extra Permission.
- **Impersonation (FR-28, WF-06, RULE-10):**
  - It is a Users Permission. Super Admin has it by wildcard; an Admin has it only as an Extra Permission.
  - The target must have a lower Role, be active and hold a registered Role. Nobody impersonates a Super Admin, and impersonation is never nested.
  - Start and end are audited.
  - A banner with a return button shows during impersonation.
  - An impersonator is not sent to the forced password change screen. If ticket 06 is resolved, fill its seam.

## Done when

- RULE-10 and RULE-11 are tested, refusals included, along with the Authorizer's Extra Permission path.
- Start and end of impersonation are tested.
- `composer check` passes.

## Comments

### Worker — 2026-11-01
- **Built:** FR-06/RULE-11: `extra_permissions` JSON column on `users`, `User::changeExtraPermissions()`, `UserRepository::extraPermissionsOf()`, second OR-source in `UserAuthorizer::can()`; Extra Permissions travel in `CreateUserCommand`/`UpdateUserCommand` (`extraPermissions`, null = unchanged) and are checked by `UserManagementGuard::ensureCanChangeExtraPermissions` (`ExtraPermissionPolicy`: Super Admin rank only; grants must be registered Permissions); events `ExtraPermissionGranted/Revoked`. Form field (CheckboxList) and detail section visible only to Super Admins. FR-28/WF-06/RULE-10: `UsersPermission::Impersonate` (no built-in Role granted; Super Admin by wildcard), `ImpersonationPolicy`, `StartImpersonation`/`EndImpersonation` commands (events `ImpersonationStarted/Ended`), `ImpersonateUserAction` on view page header and list rows, `ImpersonationSession` (session switch), Livewire `ImpersonationBanner` with return button via a `BODY_START` render hook in `AdminPanelProvider`. `AuthenticatePanel` ends an impersonation when the target is Deactivated (impersonator returns, redirect) or the impersonator is Deactivated (both sessions end). Tests for policies, entity, authorizer path, handlers with refusals, forms, and impersonation start/return/deactivation.
- **Deviations:** Extra Permissions are changed through the Create/Update commands rather than a separate command (one form save, one transaction). Nesting is passed to the start command as `alreadyImpersonating` because the session fact is only known to the UI. "Audited" = domain events only (no Audit module yet), as in 07. Ending an impersonation needs no Permission so the impersonator can always get out.
- **Seams left:** Ticket 06's forced-password-change redirect: it must skip the redirect when `ImpersonationSession::isActive()` is true (06 not present, so nothing wired). Audit listens to the four new events.
- **Open questions:** none
- **Check:** composer check green (329 tests)

### Review — 2026-11-01
- **Verdict:** approved after 0 fix rounds
- **Notes:** Extra Permissions are the second OR-source in `UserAuthorizer::can()`, stored in `users.extra_permissions` and changed via Create/Update commands (Super Admin rank only, registered Permissions only). Impersonation: the handler checks RULE-10, the UI's `ImpersonationSession` switches the session; the "already impersonating" fact comes from the UI as a command flag — accepted. `AuthenticatePanel` ends an impersonation whose target or impersonator is Deactivated. While impersonating, commands carry the target's Actor (WF-06 "acts as the target"); only start/end events name the impersonator. Seam for 06: the forced-change redirect skips when `ImpersonationSession::isActive()`.
