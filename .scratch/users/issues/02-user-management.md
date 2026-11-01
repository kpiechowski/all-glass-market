# 02 - User entity and user management (list, detail, create, edit)

Status: resolved
Blocked by: 01

Spec: FR-22..FR-25, FR-35, RULE-01..RULE-03, RULE-08, WF-04 (temporary-password path only), §8 "Information kept about a User". Decisions: D-13, D-20, D-23, D-29, D-30, D-32, D-41, D-47.

## What

- **User** as an entity with the information in spec §8. Leave out the parts later tickets add: active state (07), "password change required" (06), Extra Permissions (08) and declaration acceptance (04). Do add the Account type (always Private here) and the Otomoto shipping text.
- **List (FR-22):** name and e-mail searchable, Account type badge, Role badge with "nieznana rola", join date sortable, filters by Role and Account type. The active and password-change markers and their filters come with 06 and 07.
- **Detail (FR-23):** contact and company data, Account type, Role, Otomoto shipping text. Leave a place for Offers' statistics and tab (D-05); Offers does not exist yet, so render nothing there. The audit history comes with Audit; leave a place for it.
- **Create (FR-24, WF-04):** Role User or Admin only; Super Admin is never offered. Validation per RULE-01..RULE-03 (D-29: no unique-name and no "_" rule). Only the "set a temporary password" path; the invitation path is ticket 06. "Password change required" does not exist yet; ticket 06 wires it.
- **Edit (FR-25):** the same form. Admins see Super Admins read-only. Nobody changes their own Role. An Admin may demote an Admin to User. The Role section is hidden for Super Admins. An empty password keeps the current one.
- **Otomoto shipping text (FR-35):** a rich-text field in the admin form, with the helper text from the spec.
- **Permissions:** every action is guarded by a Users Permission through the Authorizer, and every write goes through a command carrying the Actor.

Replace today's Users resource, commands, repository, observer and `DeleteUser` path; there is no delete in V2 (D-39). The known gaps listed in `.scratch/architecture/issues/05-migrate-users.md` apply here.

## Done when

- FR-22..FR-25 and FR-35 behave as specified within the limits above. Handler tests cover each RULE-08 limit (refusals included), and there is a Livewire test per page.
- Users classes touched here leave the deptrac baseline.
- `composer check` passes.

## Comments

### Worker — 2026-11-01
- **Built:** `User` entity (plain PHP; Email and PlainPassword value objects, `AccountType` enum always Private, `UserManagementPolicy` for RULE-08), `UserMapper`, `UserRepository` port + `EloquentUserRepository`, read-only `UserModel` (also the auth model, `UserModelFactory`), `UserQueries::listing()`, migration replacing `company_account`/`has_accepted_terms` with `account_type`. 01's `UserRoles` port is folded into `UserRepository` (`roleKeyOf`, `userIdsWithRoles`, `findByEmail`, `emailIsTaken`); `AdminRecipients`, `UserAuthorizer`, Grant/RevokeSuperAdmin and `AssignUserRole` use it. `CreateUser`/`UpdateUser` handlers on Actor + entity (FR-24, FR-25, RULE-01..03, RULE-08, WF-04 temporary-password path); `DeleteUser`, `UserObserver`, old `UserRepository`/`ModelRepository` use removed. `UserManagementGuard` (Application service) gathers ranks from `RoleRegistry` and asks the policy: manage only equal or lower rank (so Admin vs Super Admin and Admin->User demotion follow the Role order), no own-Role change, Super Admin Role console-only, role must be registered and not above the actor. List (FR-22: searchable name/e-mail, Account type badge, Role badge via registry or "nieznana rola", sortable join date, filters Role and Account type), detail (FR-23), create/edit form with Role Select from registry (never Super Admin; hidden for self and Super Admins), collapsed optional password on edit, Otomoto shipping text rich editor with spec helper (FR-35); canEdit hides edit for an Admin on a Super Admin. Translations moved to `resources.php`, `enums.php`, `exceptions.php`. Livewire test per page, handler tests for each RULE-08 limit.
- **Deviations:** (1) Model moved to `Infrastructure/Persistence/Models/UserModel` and remains the authenticatable; its `save()` is allowed only when dirty keys are `remember_token`/`password` (framework sign-in plumbing); ticket 03 owns sign-in. It still implements `FilamentUser`, so two baseline lines (`FilamentUser`, `Filament\Panel`) remain until 03; every other Users baseline line and both module-boundary and four test-presence lines are gone (Audit's two models now sit in the deptrac baseline for importing `UserModel`). (2) `HasChangesLog`/`LoggableModel` dropped from the User model and `ChangesLogsRelationManager` from the resource: the audit history returns with the Audit ticket (events); until then user changes are not logged. (3) `UpdateUser` keeps the self-edit path (no ManageUsers needed for oneself, but no role change, shipping text ignored per FR-15) because `EditProfile` still sends it; `ChangeUserPassword` moved to Actor, otherwise as before (06 rebuilds). (4) `EditProfile`: Actor commands, and the company-account/terms toggles and shipping-note field removed from the profile form (account type returns with 04; shipping text is FR-15). Name unique rule dropped there too (D-29). (5) Role assignable by rank: Admin may give any registered non-Super-Admin Role with rank at most Admin's; other modules' "who may assign" is not built. (6) No domain events emitted (none defined; Audit ticket). (7) Test-first order followed per class, but I did not run each test red separately. (8) `CreateDevUserCommand` wraps its write in the write scope; it still sets no role.
- **Seams left:** invitation path, "password change required" and its marker (06); active state, marker and filter (07); Extra Permissions section (08); Account type change and declaration (04), which must also clear the `account_type` Company mapping; offers statistics/tab and audit history via `UserResource::getRelations()` / `UserInfolist`; panel access and removal of the `FilamentUser` baseline (03).
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-01
- **Verdict:** approved after 0 fix rounds
- **Notes:** RULE-08 lives in `UserManagementPolicy` (ranks from `RoleRegistry`) and is enforced by `UserManagementGuard` in Create/Update/AssignUserRole. `UserModel::save()` lets auth plumbing (`password`, `remember_token`) through the write guard — 03/06 should route password changes through commands and narrow this. User changes are not audited until the Audit rework (`HasChangesLog` dropped). Migration maps V1 `company_account && has_accepted_terms` to Company, matching ticket 10's rule; 04 adds the acceptance date/version. "Who may assign another module's Role" is not built (no such module yet).
