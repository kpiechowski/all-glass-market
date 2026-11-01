# 01 - Role and Permission registry, Authorizer, Super Admin command

Status: resolved
Blocked by: none

Spec: `.scratch/users/functional-spec.md` — FR-01..FR-05, RULE-09, §2 (Built-in Roles, Role order, "Admins" recipient group), §3, §9 "Every module". Decisions: D-02, D-08..D-12, D-19, D-20, D-47. ADR: `docs/adr/0004-own-authorizer-and-actor.md`.

## What

The foundation every other ticket and module stands on.

- **Registry.** Each module declares its Roles and Permissions to Users at boot, plus which Roles receive its own Permissions. Users holds no central role-to-permission map, and a removed module takes its Roles and grants with it (FR-02). The registry also answers which Roles exist, so later tickets can check "Role is registered" (FR-07).
- **Built-in Roles.** Users declares Super Admin > Admin > User, with the Role order from spec §2. Other modules' Roles rank below Admin unless their module declares otherwise.
- **Authorizer.** Implement `Core\Contracts\Authorizer`. A check passes for Super Admin (wildcard), or when the User's Role was granted the Permission. Extra Permissions come in ticket 08, so leave room for a second source without building it. No logic anywhere decides by Role name (FR-03), except the "Admins" recipient group, which Users exposes to other modules (D-12).
- **One Role per User** (FR-04).
- **Super Admin only from the console** (FR-05, RULE-09). A console command grants and revokes Super Admin. The existing `dev:create-root` in Core is the closest thing today; replace or move it so granting Super Admin lives in Users.

Today's code to replace: `RoleEnum` (Root/Admin/Client), `PermissionEnum`, `PermissionService`, `UserPolicy`, `PermissionDeniedException`. Structure comes from the skills (`module-layout`, `module-cqrs`, `module-persistence`, `module-testing`), not from this code.

## Done when

- A module can declare Roles, Permissions and grants, and the Authorizer answers from them. Tests cover: Super Admin passes an unknown future Permission; a granted Role passes; an ungranted Role fails; a Role whose module is absent is not registered.
- Super Admin can be granted and revoked only by the console command; it is tested.
- The "Admins" recipient group is available to other modules and returns active Users with Role Admin or Super Admin. The active/deactivated state does not exist until ticket 07, so treat every User as active and leave a note.
- Laravel Policies are no longer used for Users.
- `composer check` passes.

## Comments

### Worker — 2026-11-01
- **Built:** `RoleRegistry` ModuleApi (modules declare Roles with rank, Permission enums, and grants at boot; in memory, so an absent module leaves nothing; FR-01/02). `UserAuthorizer` implements `Core\Contracts\Authorizer` (system passes; Super Admin passes through a `holdsEveryPermission` flag it is declared with, not its name; otherwise Role grant; FR-03). Built-in `UserRole` (Super Admin 300 > Admin 200 > User 100; other modules' Roles default to 100) and `UsersPermission::ManageUsers` granted to Admin by Users' own provider. One Role per User = single `users.role` key behind the `UserRoles` port (FR-04). `AdminRecipients::userIds()` ModuleApi for the "Admins" group. Console `users:grant-super-admin` / `users:revoke-super-admin` send `GrantSuperAdmin` / `RevokeSuperAdmin` commands as `Actor::system`; a person actor is refused by `SuperAdminRolePolicy` (FR-05, RULE-09). `AssignUserRole` rewritten (Actor, string key, authorizer, refuses Super Admin in either direction, refuses unregistered Role). Migration renames role values root->super_admin, client->user. Removed `RoleEnum`, `PermissionEnum`, `PermissionService`, `UserPolicy`, `PermissionDeniedException`, `users.permissions` config, Core `dev:create-root`.
- **Deviations:** (1) Legacy Create/Update/Delete/ChangePassword handlers keep their `actorId` commands but now call `Authorizer->ensure(Actor::user(..), ManageUsers)` (self-edit and own-password stay open); tickets 02/06 rebuild them. (2) `User` model lost its `role` enum cast (role is a plain string key, so a foreign or unknown Role cannot crash reads); `canAccessPanel` interim-checks admin/super_admin keys until ticket 03 (FR-07). (3) Table/infolist role badge formats via `UserRole::tryFrom`, shows the raw key for others; the form Select offers User and Admin only (never Super Admin); ticket 02 should read the registry and add "nieznana rola". (4) Tests were written before the code, but I did not run them red separately. (5) Extra Permissions seam is a comment line in `UserAuthorizer::can()` rather than an interface.
- **Seams left:** Extra Permissions (08): add a second OR-source in `UserAuthorizer::can()`; `RoleRegistry::permissions()` lists declared Permissions for it. Deactivation (07): `AdminRecipients` must exclude Deactivated Users (note in class). Panel access (03): `RoleRegistry::isRegistered()`. Role order (02): `RoleRegistry::rankOf()`. No module calls the registry besides Users yet.
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-01
- **Verdict:** approved after 0 fix rounds
- **Notes:** `UserRoles` is a narrow port because the User entity does not exist yet; ticket 02 should fold it into the User repository and build the role Select from `RoleRegistry::roles()` with "nieznana rola". `Role` contract sits in `Users\Domain\Contracts` (shared by design). Legacy Create/Update/Delete/ChangePassword handlers are only gated by `ManageUsers` until 02/06 rebuild them. `canAccessPanel` is interim until 03. `CreateDevUserCommand` no longer finds a role enum cast on `User`, so it sets no role — dev-only.
