# 05 - Rework the Users module onto the architecture

Status: ready-for-agent
Blocked by: 02, 03, 04

Delivered through `.scratch/users/issues/01..10` (spec: `.scratch/users/functional-spec.md`), run with the `module-delivery` skill. Resolve this ticket when those are resolved.

Users predates entities, ModuleApi and the actor. Rework it to the skills, and implement `Core\Contracts\Authorizer` with roles → permissions and `Application/ModuleApi/RoleRegistry`, so other modules can register roles. Check `.scratch/users/` discovery and functional spec first: behaviour comes from the spec, structure from the skills.

Known gaps: concrete `UserRepository` on `ModelRepository`; model in `Domain/Models`; `Domain/Observers/UserObserver`; Laravel `UserPolicy`; `EditUser` with a bare `DeleteAction`; commands carrying no `Actor`; translations in `resource.php` / `profile.php`; no tests.

Done when the Users entries leave the deptrac baseline and `composer check` passes.
