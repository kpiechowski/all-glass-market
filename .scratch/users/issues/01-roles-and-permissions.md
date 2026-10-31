# 01 - Role and Permission registry, Authorizer, Super Admin command

Status: ready-for-agent
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
