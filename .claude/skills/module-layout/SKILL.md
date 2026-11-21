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
    Repositories/      ports (interfaces): {X}Repository on entities,
                       {X}Reader / {X}Search returning read models
    ReadModels/        readonly shapes a read port returns: values only
    Policies/          {X}DomainPolicy — pure rules on values
    ValueObjects/      Money, Email, Eurocode — validate themselves
    Events/            readonly business facts, past tense
    Exceptions/        extend Core\Exceptions\DomainException
    Enums/             may implement Filament enum contracts (ADR 0003);
                       includes {Name}Permission implementing Core\Contracts\Permission
    Contracts/         interfaces another module implements, plus the
                       types their methods take and return
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
| A read the module needs that is not loading one entity (search, facts for a ModuleApi read) | port `Domain/Repositories/{X}Reader` or `{X}Search`, returning `Domain/ReadModels/*`; adapter `Infrastructure/Persistence/Eloquent{X}Reader` | `module-persistence`, Read ports |
| Data returned across a module boundary | DTO beside the `ModuleApi` class, mapped from the entity or read model | ids and values, never entities, models, read models or markup |
| Appearance another module shows (a thumbnail, a badge of this module's data) | Blade component in this module's `UserInterface/resources/views/components/`, listed in the allowed table below | the DTO carries values; the component draws them |
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
| `UserInterface/Filament/GlassModelPicker/*` | yes — Catalogue's embeddable Glass model picker (`GlassModelPickerSlideOver`, `GlassModelPickerField`); the embedder's command re-checks the chosen id |
| `UserInterface/Filament/Resources/*/Support/*ViewSections` | yes — Catalogue's `DictionaryValueViewSections` and `GlassModelViewSections`: a registry another module or integration calls from its provider to add a section to the view page |
| Blade components in `UserInterface/resources/views/components/` that the owner publishes for others | yes — `<x-{module}::…>`, fed with the owner's ModuleApi DTO (example: `<x-catalogue::glass-thumbnail :thumbnail="$summary->thumbnail" />`, fed with Catalogue's `Thumbnail`) |
| everything else | private to module B |

A new row in this table is a decision of the user: propose it in the issue log, never add the import first.

**`ModuleApi` is the Open Host Service: other modules and integrations call it, the module itself never does.** Inside the module, UserInterface sends commands and reads `{X}Queries`; handlers share logic through `Application/Services/`, and a handler that reuses a whole use case of its own module sends that command on the bus. The module's service providers are the one exception: they wire registries.

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

## Unmapped: when no rule covers it

A class or flow that fits no row above and has no sibling in the codebase is **unmapped**. Then:

1. Take the nearest pattern this skill set describes, keep the dependency direction of the Layers table in `CLAUDE.md` (Domain depends only on Core; Application on Domain; Infrastructure and UserInterface point inward), and keep within the existing folders and public surfaces (`ModuleApi`, `Contracts`, the allowed table).
2. Record it in the issue log under **Unmapped:** what you built, where, and which pattern you followed. The manager reviews it, and the skill gains a rule for it.

## Before you finish

- Every new class sits in the folder the tables above give it, or is listed under **Unmapped:** in the issue log.
- Every import from another module is in the "allowed" rows.
- No class of this module imports its own `ModuleApi`, except its service providers.
- No folder was created empty.
