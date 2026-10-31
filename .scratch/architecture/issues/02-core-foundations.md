# 02 - Core foundations for entities, actor, authorization and UI writes

Status: ready-for-agent

Build the Core pieces the skills already name, so that a new module can follow them. Use the exact names below, since the skills reference them. Load `module-layout`, `module-cqrs`, `module-persistence`, `module-testing`. Work test-first.

## Scope

- `Core\Domain\Actor`: final readonly value object; `Actor::user(int $id)`, `Actor::system(string $reason)`, `isSystem()`, `id`, `reason`.
- `Core\Contracts\Permission` (interface for module permission enums) and `Core\Contracts\Authorizer` with `can(Actor, Permission): bool` and `ensure(Actor, Permission): void`. `ensure()` throws `Core\Exceptions\AuthorizationException`. A system actor passes. Users implements it in ticket 05; until then bind nothing and use a test double in tests.
- `Core\Exceptions\DomainException`: abstract, carries a translation key and parameters.
- `Core\Infrastructure\Persistence\ReadOnlyModel`: abstract Eloquent model. `save()`, `update()`, `delete()`, `push()` and `forceDelete()` throw `ReadOnlyModelException` outside `ReadOnlyModel::writing(callable)`.
- `Core\Contracts\Mapper` (`toEntity(Model)`, `toRow(object): array`) and a rewritten `Core\Contracts\Repository` on entities: `get`, `find`, `add(): int`, `save`, `remove`. Rewrite `Core\Abstracts\EloquentRepository` with the constructor `(string $modelClass, Mapper $mapper)`: it writes via `fill()->save()` inside the write scope and keeps `query()` protected.
- Core base factory: opens the write scope on `create()`.
- `Core\Domain\Events\RecordChanged`: model class, id, `array<string, array{old: mixed, new: mixed}>` changes.
- A transaction around every command on the Ecotone command bus. Check `search-docs` / Ecotone docs for the interceptor or the built-in Laravel transaction support.
- `Core\UserInterface\CommandSender::send(object $command): mixed`: sends on the bus. On `DomainException` it shows a translated danger `Notification` and halts the Filament action or page.
- `Core\UserInterface\CurrentActor`: `get(): Actor` (`Actor::user(auth()->id())`) and `can(Permission): bool`.
- `core::common` translation file for generic words.
- Move `Core/Http/Cli/` to `Core/Console/`.

## Out of scope

`ModelRepository`, `HasCommandBus` and `HasQueryBus` stay until tickets 05 and 06 migrate Users and Audit, then get deleted.

## Done when

Every class above has its test written first. `dartisan test` and `vendor/bin/pint --dirty` pass.
