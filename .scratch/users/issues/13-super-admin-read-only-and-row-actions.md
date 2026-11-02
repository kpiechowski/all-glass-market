# 13 - Another Super Admin is read-only; grouped row actions

Status: ready-for-agent
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
