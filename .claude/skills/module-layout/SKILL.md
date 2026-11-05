---
name: module-layout
description: "Where code belongs. Use when creating a module or integration, placing a new class, or when one module needs something from another — the module tree, what each folder holds, ModuleApi, module vs integration."
---

# Module layout

Hexagonal architecture with DDD-lite modules. The domain is plain PHP; Eloquent and Filament are adapters around it. Keep it lite: add a class when a rule below asks for it, never speculatively.

## The module tree

```
app/Modules/{Name}/
  {Name}ModuleServiceProvider.php   bindings, event provider, migrations
  Domain/
    Entities/          plain PHP entities: state + behaviour + invariants
    Repositories/      ports (interfaces) on entities
    Policies/          {X}DomainPolicy — pure rules on values
    ValueObjects/      Money, Email, Eurocode — validate themselves
    Events/            readonly business facts, past tense
    Exceptions/        extend Core\Exceptions\DomainException
    Enums/             may implement Filament enum contracts (ADR 0003);
                       includes {Name}Permission implementing Core\Contracts\Permission
    Contracts/         interfaces other modules implement or consume
  Application/
    Commands/{Verb}{Thing}/   {Verb}{Thing}Command + {Verb}{Thing}CommandHandler
    Queries/{Verb}{Thing}/    only for reads feeding a decision or calculation
    ModuleApi/         the module's public surface for other modules
    Services/          logic shared by several handlers of this module
    Listeners/         reactions to events
    Jobs/              thin: dispatch a command
    Providers/         {Name}EventServiceProvider
  Infrastructure/
    Persistence/
      Models/          Eloquent models — read-only outside the adapter
      Mappers/         {X}Mapper: model ↔ entity
      Queries/         {X}Queries: named Builder methods for display
      Observers/       emit Core RecordChanged, nothing else
      Eloquent{X}Repository.php   the adapter implementing the port
    migrations/  Factories/  Seeders/  config/
  UserInterface/
    Filament/          Resources, Pages, Widgets, Clusters, RelationManagers
    Http/              Controllers, Requests, Resources (API)
    Console/           Artisan commands
    resources/         lang/pl/*.php, views
```

Create a module with `dartisan make:module {Name} --no-interaction`; the scaffold is the reference layout. Create a folder only when its first class arrives.

## Where does this go?

| Writing | Place | Rule |
|---|---|---|
| Behaviour that changes one entity's state | method on the entity | it guards its own invariants |
| Rule the UI also needs for visibility | `Domain/Policies/{X}DomainPolicy`, taking values | the entity calls it too (`module-cqrs`) |
| Rule spanning several entities, no I/O | `Domain/Policies/` | pure; receives everything it judges |
| Logic shared by several handlers | `Application/Services/` | may use ports; single-handler logic stays in the handler |
| Something another module needs to do or ask | `Application/ModuleApi/` | see below |
| Value with format rules, no identity | `Domain/ValueObjects/` | validates in its constructor |
| Data returned across a module boundary | DTO beside the `ModuleApi` class | ids and values, never entities or models |
| Payload shaped by an external API | `Integrations/{Name}/Infrastructure/Mappers/{X}PayloadMapper` | foreign shape stays at the edge |
| Background work | `Application/Jobs/` | dispatches a command |
| Artisan command | `UserInterface/Console/` | builds and dispatches a command |

## Crossing a module boundary

A module **exposes operations, not data**. Another module may import exactly:

| From module B | Allowed in module A |
|---|---|
| `Application/ModuleApi/*` and its DTOs | yes — the front door |
| `Domain/Events/*` | yes — to listen |
| `Domain/Contracts/*`, `Domain/Enums/*` | yes — meant to be shared |
| `Infrastructure/Persistence/Models/*` | read only: Eloquent relations and Filament display |
| `UserInterface/Filament/RelationManagers/*` | yes — a hosting resource lists it in `getRelations()` (Audit's `RecordHistoryRelationManager`); deptrac already lets UserInterface use UserInterface |
| everything else | private to module B |

**`ModuleApi` classes** are named by capability (`RoleRegistry`, `UserAccounts`), not `{Name}Service`. A write method dispatches a command of its own module, so there is one write path with one set of rules and events. A read method returns an id, a DTO or a scalar.

```php
// Workshops registers its role; it never learns how Users stores roles
$this->roleRegistry->register(WorkshopRole::Owner, [WorkshopPermission::ManageWorkshop]);
```

Choose the channel by what the caller needs:

| Need | Use |
|---|---|
| Tell whoever cares that something happened | domain event (`module-events`) |
| Get a result back, or have it happen in the same transaction | `ModuleApi` call |

## Module or integration?

A **module** owns business meaning that survives any vendor. An **integration** (`app/Integrations/{Name}`, same four layers, `dartisan make:integration`) exists because an external platform does; deleting it touches no module. Integrations listen to domain events and call `ModuleApi`.

Settled boundaries: Otomoto and WooCommerce are integrations. Vehicles (Car, Eurocode) is a module. Reservations, Sales and OfferInquiry live in Offers. ChangesLog lives in Audit. Settings is a flat key-value module. Submissions is its own module.

## Core

Core holds what every module needs: contracts (`Repository`, `Authorizer`, `Permission`), base classes (`EloquentRepository`, the read-only base model, base factory, `DomainException`), the `Actor` value object, the shared `RecordChanged` event, bus concerns for Filament, and the `make:module` / `make:integration` commands. Core depends on no module.

## Before you finish

- Every new class sits in the folder the tables above give it.
- Every import from another module is in the "allowed" rows.
- No folder was created empty.
