# 13 - Another Super Admin is read-only; grouped row actions

Status: resolved
Blocked by: none

Spec: FR-25, RULE-08, §3 Super Admin row, §11 "A Super Admin opens another Super Admin". Decision: D-48 (replaces part of D-23). Skill: `filament-module-ui` "Record actions" row.

## What

1. **Another Super Admin's account is read-only to everyone (D-48).**
   - The handlers refuse when the actor is not the target and the target is a Super Admin. This covers `UpdateUser`, `ChangeUserPassword`, `SendPasswordLink`, `SetPasswordChangeRequirement` and any other command that manages another User through `UserManagementGuard`. Put the rule in `UserManagementPolicy` so the form and the handler ask the same method.
   - The UI follows: no "Edytuj" on the row or detail page, and no password, link or marker actions on another Super Admin.
   - A Super Admin still edits their own data in "Mój profil". Deactivation and impersonation of a Super Admin stay refused as before.
2. **Grouped row actions.** Both Users tables follow the skill rule: one visible action stands alone; two or more go into one `ActionGroup`.
   - `UsersTable`: Podgląd, Edytuj, Wejdź na konto.
   - `JournalEntriesTable`: Podgląd, Przejdź do obiektu, Oznacz jako przeczytane, Usuń.

## Done when

- Handler tests: a Super Admin is refused on every command above against another Super Admin, and still allowed against Users and Admins.
- Livewire tests: a Super Admin sees no edit or password actions on another Super Admin's row and detail; both tables render their record actions inside an `ActionGroup`.
- `composer check` passes.

## Comments

### Worker — 2026-11-02
- **Built:** D-48 / RULE-08: `UserManagementPolicy::canEditAccountOf` (not self + target Super Admin = refused, else rank rule) and `UserManagementGuard::ensureCanEditAccount`, used by `UpdateUser`, `ChangeUserPassword` (non-self branch), `SendPasswordLink`, `SetPasswordChangeRequirement`. UI mirrors it through `RoleDisplay::canEditAccount` -> `UserResource::canEdit` (edit, edit page, password/link/marker actions all follow). Both Users tables put record actions in one `ActionGroup`. Tests added/updated (policy, 4 handlers, ListUsers, ListJournalEntries, EditUser).
- **Deviations:** `ReactivateUser` and `AssignUserRole` keep `ensureCanManage` / their own Super Admin refusal (a Super Admin cannot be deactivated, role moves are console-only). A Super Admin's own record stays editable in the user form (issue says "actor is not the target"). Row-action visibility tests read the group's actions directly because Filament's `assertTableActionHidden` does not resolve record state inside a group.
- **Seams left:** none
- **Open questions:** none
- **Check:** composer check green

### Review — 2026-11-02
- **Verdict:** approved after 0 fix rounds
- **Notes:** D-48 lives in `UserManagementPolicy::canEditAccountOf`, enforced by `UserManagementGuard::ensureCanEditAccount` in UpdateUser, ChangeUserPassword (non-self), SendPasswordLink and SetPasswordChangeRequirement. ReactivateUser and AssignUserRole keep `ensureCanManage` (a Super Admin can't be deactivated, and their Role is console-only). Three older tests changed to D-48. Both Users tables group record actions in one `ActionGroup`.
