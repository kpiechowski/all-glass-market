# 03 - make:module, make:integration and stubs match the architecture

Status: resolved
Blocked by: 02

`make:module` and `make:integration` generate exactly the layout in `module-layout`. Agents copy what the scaffold shows, so a wrong stub turns into a wrong module.

## Scope

- Module scaffold: entity, repository port, `{X}DomainPolicy`, `{Name}Permission` enum, `Domain/Events`, `Domain/Exceptions`; `Application/Commands`, `ModuleApi`, `Listeners`, `Services`, `Jobs`, `Providers/{Name}EventServiceProvider`; `Infrastructure/Persistence/{Models,Mappers,Queries,Observers}` with `{X}Model extends ReadOnlyModel`, `{X}Mapper`, `{X}Queries`, `Eloquent{X}Repository` and its binding, plus a factory extending `Core\Infrastructure\Factories\ModelFactory`; `UserInterface/Filament`, `UserInterface/Console`; `lang/pl/{resources,enums,exceptions}.php`.
- Matching test skeletons under `tests/Unit|Feature/Modules/{Name}/` for the entity, the policy, the mapper and the repository.
- Fix `make:integration`: the mapper lands in `Infrastructure/Mappers/`, matching its stub namespace (today it writes to `Domain/Mappers/`).
- Drop stubs that no longer match (old `repository.stub` semantics, the observer doing more than `RecordChanged`).

## Done when

`dartisan make:module Sandbox --no-interaction` produces a module that passes `composer check` (once ticket 04 lands) and `dartisan test`. Delete it afterwards with `dartisan delete:module` or by hand.

## Answer

Implemented by a subagent, verified 2026-10-31. A generated module plus integration pass the full `composer check` (105 tests, 0 deptrac violations). `ScaffoldCommandsTest` regenerates both and runs their tests, deptrac and `tests/Unit/Architecture`.

Decisions taken during implementation:
- No empty folders. The scaffold generates only files that carry content, following `module-layout`.
- No command or handler exemplar. A handler needs an `Authorizer` binding, which arrives with ticket 05.
- `make:module` no longer prompts.
- The integration payload mapper is `{X}PayloadMapper`, so it never clashes with the entity `{X}Mapper`.
- `ModuleServiceProvider::registerMorphMap` scans `Infrastructure/Persistence/Models` (key without the `Model` suffix) as well as `Domain/Models`. Drop `Domain/Models` after tickets 05 and 06.

Follow-up for ticket 05: once the Authorizer exists, consider adding a command and handler exemplar with its test to the scaffold.
