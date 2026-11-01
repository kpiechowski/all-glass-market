# 07 - Deactivate and reactivate Users

Status: resolved
Blocked by: 03

Spec: FR-27, RULE-08, WF-05, WF-01 deactivated branch, WF-03 deactivated branch, §7 User activity, §11 deactivation cases. Decisions: D-39, D-47 (A-10 rejected: nobody deactivates a Super Admin from the panel).

## What

- **Deactivate and reactivate** from the user detail or edit page, with a confirmation step.
  - An Admin can deactivate Users and Admins. Nobody can deactivate a Super Admin, and nobody can deactivate themself.
  - Deactivation ends all the User's sessions at once and announces "User deactivated"; reactivation announces "User reactivated". Both are domain events, audited.
- **Effects:**
  - a Deactivated User cannot sign in (WF-01 message) and gets no reset link, only the neutral response;
  - the "Admins" group contains active Users only;
  - the list shows an inactive marker and has an active/inactive filter.
- **No delete anywhere.**

## Done when

- WF-05 is tested, including every refusal from RULE-08.
- Sign-in refusal and the neutral reset response for Deactivated Users are tested.
- If ticket 06 is already resolved, its deactivated seam is filled; otherwise leave a note.
- `composer check` passes.

## Comments

### Worker — 2026-11-01
- **Built:** FR-27/WF-05/RULE-08: `deactivated_at` column, entity `deactivate()/reactivate()/isActive()`, `UserManagementPolicy::canDeactivate` (not self, never Super Admin, not above rank) enforced by `UserManagementGuard::ensureCanDeactivate`; commands `DeactivateUser`/`ReactivateUser` (authorize, guard, save, dispatch `UserDeactivated`/`UserReactivated`). `AdminRecipients` leaves out Deactivated Users; Login refuses them (`users::login.deactivated`); `AuthenticatePanel` logs out a User deactivated mid-session (this ends all their sessions on the next request); the Journal still accepts entries (tested). UI: Deactivate (with confirmation) and Reactivate actions on view and edit pages, "konto nieaktywne" marker in list and detail, active/inactive filter. No delete added.
- **Deviations:** "Ends all sessions at once" is done by the middleware check on every request, not by purging the `sessions` table. "Audited" = the two domain events; no observer or Audit module exists yet, so no `RecordChanged` wiring.
- **Seams left:** WF-03 deactivated branch: ticket 06's reset flow must call `UserModel::isActive()` / `User::isActive()` and return the neutral response; 06 was not present so nothing built. Impersonation mid-session end (§11) belongs to the impersonation ticket (it can rely on `AuthenticatePanel` plus `isActive()`). Offers/Audit listen to `UserDeactivated`/`UserReactivated`.
- **Open questions:** none
- **Check:** composer check green (288 tests). Docker Desktop had to be started to run it.

### Review — 2026-11-01
- **Verdict:** approved after 0 fix rounds
- **Notes:** "End all sessions at once" is enforced per request by `AuthenticatePanel` (logs a Deactivated User out), not by purging the sessions table — accepted. Audit is only the `UserDeactivated`/`UserReactivated` events until the Audit rework. Open seams: 06's reset flow must give a Deactivated User the neutral response (check `User::isActive()`); 08 must end an impersonation of a Deactivated User and return the impersonator.
