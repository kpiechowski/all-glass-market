# 03 - make:module, make:integration and stubs match the architecture

Status: ready-for-agent
Blocked by: 02

`make:module` and `make:integration` generate exactly the layout in `module-layout`. Agents copy what the scaffold shows, so a wrong stub turns into a wrong module.

## Scope

- Module scaffold: entity, repository port, `{X}DomainPolicy`, `{Name}Permission` enum, `Domain/Events`, `Domain/Exceptions`; `Application/Commands`, `ModuleApi`, `Listeners`, `Services`, `Jobs`, `Providers/{Name}EventServiceProvider`; `Infrastructure/Persistence/{Models,Mappers,Queries,Observers}` with `{X}Model extends ReadOnlyModel`, `{X}Mapper`, `{X}Queries`, `Eloquent{X}Repository` and its binding, plus a factory on the Core base factory; `UserInterface/Filament`, `UserInterface/Console`; `lang/pl/{resources,enums,exceptions}.php`.
- Matching test skeletons under `tests/Unit|Feature/Modules/{Name}/` for the entity, the policy, the mapper and the repository.
- Fix `make:integration`: the mapper lands in `Infrastructure/Mappers/`, matching its stub namespace (today it writes to `Domain/Mappers/`).
- Drop stubs that no longer match (old `repository.stub` semantics, the observer doing more than `RecordChanged`).

## Done when

`dartisan make:module Sandbox --no-interaction` produces a module that passes `composer check` (once ticket 04 lands) and `dartisan test`. Delete it afterwards with `dartisan delete:module` or by hand.
