# Architecture grilling: decision log

Working log of the 2026-10-31 architecture grilling. Source material: `docs/archive/README.md`, `docs/archive/old_skills/`. Final output goes to `CLAUDE.md`, `.claude/skills/`, `docs/adr/`.

## Settled

| # | Decision |
|---|---|
| D1 | **Entity + Mapper + one Eloquent model (option C).** Domain operates on plain entities; the Eloquent model is kept for Laravel/Filament features and is read-only everywhere except the repository adapter. |
| D2 | **Docs: one source per rule.** `CLAUDE.md` = short map + golden rules + step→skill table. Per-area skills hold detail. `docs/adr/` holds the "why" of trade-offs. No umbrella architecture doc. |
| D3 | **Commands** in `Application/Commands/{Verb}{Thing}/` with `{Verb}{Thing}Command` + `{Verb}{Thing}CommandHandler`. Business verb when the intent has a name. |
| D4 | **Reads:** display reads for Filament = named method returning `Builder`, no query bus. Read feeding a decision/calculation = Query + Handler. Every write = Command, no exceptions. |
| D5 | **Filament split thresholds:** Table class holds query/sort/pagination/actions; columns inline ≤ 6, else `{X}TableColumns`. Filters always own class `{X}TableFilters`. Form/Infolist always own class; a section gets its own class at > ~8 fields or when reused. Action gets own class in `Actions/` when it has a modal/schema or a chain > ~5 calls. Standalone page: flat file if simple, directory if it has extracted schemas. Inside a Form/Infolist/Table class, split content into private methods (sections, small non-reusable actions). |
| D6 | **Translations** per module namespace, one file per UI surface: `resources.php` (top key = resource), `pages.php`, `mail.php`, `notifications.php`, `enums.php`, `exceptions.php`. Shared words from Filament or `core::common`. |
| D7 | **Relationship-saving Filament fields are forbidden as write paths.** Relations and media travel in command data; `->relationship()` only for loading options/reading. |
| D8 | **Integrations** stay in `app/Integrations/{Name}` with the same layers; listen to domain events, call modules' public surface only. Payload mappers in `Infrastructure/Mappers/` (fix `make:integration` path mismatch). |
| D9 | **No `app/Shared`.** Shared needs go to Core, or a small module. |
| D10 | **Module public surface = `Application/ModuleApi/`**, separate from internal `Application/Services/`. Classes named by capability (`RoleRegistry`), with their DTOs. Writes dispatch a command; reads return id/DTO. Other modules may import only `ModuleApi/`, `Domain/Events/`, `Domain/Contracts/` and read models. HTTP API is a driving adapter in `UserInterface/Http/`, same role as Filament. |
| D11 | **Entity shape:** `Domain/Entities/{X}` plain PHP, private state, named constructors, behaviour methods guarding invariants. `Infrastructure/Persistence/Mappers/{X}Mapper` with `toEntity(Model)` / `toRow(Entity)`. Port `Domain/Repositories/{X}Repository` on entities: `get`, `find`, `add(): int`, `save`, `remove` + domain methods. Int autoincrement ids, `?int $id` until `add()`. Core repository contract/abstract rewritten for entities; `ModelRepository` removed after migration. |
| D12 | **Eloquent model in `Infrastructure/Persistence/Models/`.** Documented read exception: UserInterface may import models (read-only) and `{X}Queries` from `Infrastructure/Persistence`, nothing else from Infrastructure. Cross-module Eloquent relations allowed (read). |
| D13 | **Display reads in `Infrastructure/Persistence/Queries/{X}Queries`**, concrete class, no interface, methods return `Builder`. |
| D14 | **Validation in five layers:** UI (UX only) → Command typed shape → Value Objects (format) → entity methods (invariants; `{X}DomainPolicy` for multi-entity rules, pure) → handler gathers facts needing data via ports / other modules' ModuleApi and passes them in; DB constraints as last line. Domain never queries. |
| D15 | **Authorization:** every command carries `actorId`. Port `Core\Contracts\Authorizer` (`can`, `ensure`), implemented by Users. Each module defines its permission enum implementing `Core\Contracts\Permission`; modules check permissions, never roles. Roles→permissions mapping belongs to Users; other modules register roles via `Users\Application\ModuleApi\RoleRegistry`. Handler calls `ensure()`; Filament uses a Core trait (`actorCan()`) in `visible()`. No Laravel Policies. |
| D17 | **Small aggregates.** A part lives inside the parent entity only if it cannot exist without the parent, is never edited alone, and is few. Otherwise separate entity + repository, referenced by id. |
| D18 | **Events:** business facts are plain Laravel events in `Domain/Events/` (readonly, past tense, ids + values), dispatched by the command handler after save; listeners in `Application/Listeners/`, registered in the module `EventServiceProvider`. Adapter writes via `$model->fill()->save()` inside the write scope, so observers fire. Observers in `Infrastructure/Persistence/Observers/` are thin and emit only the generic Core `RecordChanged` (class, id, diff) for Audit; they never infer intent. |
| D19 | **UI rule visibility:** a rule the UI needs for `visible()` lives in `{X}DomainPolicy` taking values (not entities); the entity calls it, the UI calls it with model fields. UI may import `Domain/Policies`. |
| D20 | **Transactions:** every command runs in a DB transaction via a Core bus interceptor. Listeners with external side effects implement `ShouldHandleEventsAfterCommit`. |
| D21 | **Return values:** create handler returns `int` id, Filament page returns `Model::findOrFail($id)`; update/delete return `void`, page returns `$record->refresh()`. No models or entities leave a module through a handler. |
| D22 | **Commands carry primitives**; the handler builds value objects. |
| D23 | **Domain exceptions** extend `Core\Exceptions\DomainException` with a translation key (`exceptions.php`); a Core trait for Filament pages/actions catches them and shows a danger notification, keeping the form open. |
| D16 | **Definition of done:** `composer check` = pint --test + deptrac + phpunit. Minimum tests: entity/VO/policy unit; handler via bus happy + refusal; repository domain methods and `{X}Queries` incl. exclusion; mapper round-trip; one Livewire test per resource for create/edit/delete through commands. Core base factory opens the write scope. `deptrac/deptrac` approved for require-dev. |

| D24 | **Jobs** in `Application/Jobs/`, thin: dispatch a command. **Module Artisan commands** in `UserInterface/Console/`, build + dispatch a command. `Core/Http/Cli/` moves to `Core/Console/`. |
| D25 | **Internal `Application/Services/`**: logic shared by several handlers of the same module; may use repository ports; never called from UI or other modules. Single-handler logic stays in the handler. |
| D26 | **Domain enums** may implement Filament enum contracts (`HasLabel`, `HasColor`, `HasIcon`, `HasDescription`, extendable) and use Core enum traits/contracts (e.g. `HasCoreEnumFunctions`). No other Filament import in Domain. |
| D27 | `make:module` / `make:integration` and stubs generate exactly the agreed layout. |
| D28 | **Skills state target rules and descriptions only**, no "migration status" sections; the archived skills are not a pattern. Users and Audit get rework tickets; deptrac baseline until then. |
| D29 | Six skills (`module-layout`, `module-cqrs`, `module-persistence`, `module-events`, `filament-module-ui`, `module-testing`), each ending with a "Before you finish" checklist that doubles as review standards. `module-discovery` stays untouched. |
| D30 | ADRs 0001 entity+mapper+read-only model, 0002 UI reads bypass query bus, 0003 domain enums with Filament contracts, 0004 own Authorizer instead of Laravel Policies. No GLOSSARY.md yet (domain-only). |
| D31 | Rollout tickets: 1 docs, 2 Core, 3 scaffolding, 4 deptrac + `composer check`, 5 Users, 6 Audit. This session delivers 1 and writes 2–6. |

| D32 | **Actor value object** `Core\Domain\Actor` replaces `int $actorId` in every command: `Actor::user($id)` for anything a person started (jobs a user triggered carry that user's actor), `Actor::system('<reason>')` only for scheduler, integration webhooks and listeners. `Authorizer` lets a system actor through; the reason reaches Audit. UserInterface (Filament, HTTP, Console run by a person) never builds `Actor::system()`. `Actor` is the only non-primitive allowed in a command (ADR 0004). |
| D33 | **TDD for rules and write paths:** entities, value objects, domain policies, mappers, `{X}Queries`, repository domain methods and command handlers are written test-first (red → green). Filament resource tests may follow the code. A test-presence check in `composer check` fails when one of those classes has no matching test. |

## Status

Grilling closed 2026-10-31; shared understanding confirmed. Rollout tickets in `issues/`.
