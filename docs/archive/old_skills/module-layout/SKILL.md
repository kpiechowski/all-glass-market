---
name: module-layout
description: "Where code belongs in this codebase. Use when creating a module or integration, placing a new class, moving files between layers, or answering \"where does this go?\" — repositories, listeners, observers, services, domain policies, DTOs, value objects. Also covers module vs integration, what one module may use from another, and the Filament trade-offs that bend the layer rules."
---

# Module layout

This codebase is ports & adapters with DDD module separation. Business modules live in `app/Modules/{Name}`, external-system adapters in `app/Integrations/{Name}`, shared abstracts in `app/Core`.

Every module has the same four layers. The layer answers a different question:

| Layer | Answers | May depend on | Must not touch |
|---|---|---|---|
| **Domain** | what the thing *is* | nothing outside itself | Application, Infrastructure, UserInterface |
| **Application** | what the system *does* | Domain | Infrastructure internals, Filament, HTTP |
| **Infrastructure** | *how* it is done | Domain | Application, UserInterface |
| **UserInterface** | how it is *shown* | Application (via buses), Domain models (read) | repositories, direct model mutation |

Dependencies point inward. Domain is the centre and knows nothing about what surrounds it — with two documented exceptions, below.

## Anatomy

```
app/Modules/{Name}/
  {Name}ModuleServiceProvider.php     ← entry point, bindings, event providers
  Domain/
    Models/                           Eloquent models as entities (trade-off, below)
    Repositories/                     INTERFACES only — the ports
    Policies/                         {Name}DomainPolicy — business rules, answer bool
    Enums/  ValueObjects/  Events/  Exceptions/  Contracts/  Traits/
    Services/                         rare: domain logic spanning entities, no I/O
  Application/
    Commands/{Action}{Name}/          {Action}{Name}Command + …CommandHandler
    Queries/{Action}{Name}/           {Action}{Name}Query + …QueryHandler
    Listeners/                        reactions to domain events
    Services/                         orchestration; also this module's public API
    Dto/                              data crossing a boundary
    Policies/  Providers/
  Infrastructure/
    Persistence/                      Eloquent{Name}Repository — the adapters
      Observers/                      thin ORM hooks; emit events, nothing else
    migrations/  Factories/  Seeders/  config/
  UserInterface/
    Filament/                         Resources, Pages, Schemas, Tables, Widgets
    resources/                        views, lang, scripts, styles
```

## Where does this go?

| Writing | Put it in | Why |
|---|---|---|
| Repository **interface** | `Domain/Repositories/{Name}Repository` | the port: domain states what it needs |
| Repository **implementation** | `Infrastructure/Persistence/Eloquent{Name}Repository` | the adapter: Eloquent is a detail |
| Listener | `Application/Listeners/` | reacting to an event is orchestration |
| Observer | `Infrastructure/Persistence/Observers/` | hooks the ORM lifecycle, so it is infrastructure |
| Business rule answering "is this allowed?" | `Domain/Policies/{Name}DomainPolicy` | the rule itself, decided in one place |
| Logic over several entities, no I/O | `Domain/Services/` | rare; see below before reaching for it |
| Orchestration, calls repositories or other modules | `Application/Services/` | coordinates, not a rule in itself |
| Value with rules, no identity (`Money`, `Eurocode`) | `Domain/ValueObjects/` | validates itself, belongs to the business |
| Data shaped by the UI (`OfferSummaryDto`) | `Application/Dto/` | shape is dictated by the consumer |
| Data shaped by an external API | `Integrations/{Name}/Infrastructure/` | shape is dictated by a foreign system |

**The port keeps the domain name, the adapter gets the `Eloquent` prefix.** Handlers type-hint `UserRepository` and never learn which implementation they got. Bind them in the module service provider:

```php
protected array $containerBindings = [
    UserRepository::class => EloquentUserRepository::class,
];
```

**DTO or value object?** Ask what fixes its shape. Business rules → value object, in Domain. A consumer (Filament, an API) → DTO, in Application or Infrastructure. `Domain/Dto/` is legitimate only for data that never leaves the domain; don't create the directory speculatively.

**Observers stay thin.** An observer emits a domain event; a listener does the work. Never resolve a service inside `booted()` or an observer method — that is how business logic ends up wired to the ORM lifecycle where nothing can test it.

**`Domain/Services/` is a last resort.** Most logic belongs on the entity, in a value object, or in a domain policy. Reach for a domain service only when behaviour genuinely spans several entities and sits on none of them — and keep it free of I/O and framework calls. Anything that loads or saves is orchestration: `Application/Services/`.

### Domain policies

A domain policy holds a business rule and answers whether something is allowed. It is not Laravel authorization.

```php
final class OfferDomainPolicy
{
    public function canBeActivated(Offer $offer): bool
    {
        return $offer->status === OfferStatus::Draft
            && $offer->price !== null
            && $offer->vehicle !== null;
    }
}
```

Handlers ask the policy before acting, so the rule lives in one place instead of being re-derived at every call site:

```php
if (! $this->offerPolicy->canBeActivated($offer)) {
    throw new OfferCannotBeActivatedException();
}
```

- Named `{Name}DomainPolicy`. Split into narrower policies when one class starts covering unrelated rules.
- Methods read as questions — `canBeActivated`, `isEligibleForDiscount` — and return `bool`. A rule needing to explain *why* it failed returns a result object or throws; it does not return a string.
- Pure: no repositories, no I/O. It receives everything it judges. That is what makes it testable without a database.

**Not to be confused with `Application/Policies/`**, which holds Laravel Gate policies (`UserPolicy`) answering "may *this user* do this?" for Filament and the authorization layer. Domain policy = business rule. Application policy = permission check. When both apply, the Gate policy may consult the domain policy, never the reverse.

## Module or integration?

A **module** owns business meaning that survives any vendor. An **integration** exists because some external platform does, and deleting that platform must not touch a single domain class.

| Decision | Reason |
|---|---|
| Otomoto, WooCommerce → `Integrations/` | external platform dependency; removal must not reach the domain |
| Vehicles (Car, Eurocode) → own module | eurocodes and cars exist without any offer |
| Reservations, Sales → inside Offers | no existence outside an offer |
| OfferInquiry → inside Offers | an inquiry is an offer lifecycle event |
| ChangesLog → Audit module | cross-cutting; any module may use it |
| Settings → single flat module | key-value config, no real domain logic |
| Submissions → own module | unrelated to offers, clean boundary |

Integrations keep the same four layers. They listen to domain events and call outward; they never reach into a module's internals.

## What one module may use from another

**Expose operations, not data.** A module may hand another module a use case. It may not hand over a repository, a model to mutate, or any other internal part.

The failure this prevents, from a real project: `CreateBusinessCommandHandler` needed a user, so it injected `UserRepository` and called `create([... 'role' => Owner])` directly. Two things broke. The layer rule, obviously — but worse, **Business now owned an invariant belonging to Users**: it had to know which role makes a valid business owner. The day that rule changed in Users, Business would have kept the stale one and nothing would have caught it.

The fix was a method on the Users module's service:

```php
// in Business's handler — cannot get the role wrong, because it never sets it
$userId = $this->userService->createUserForBusinessOwning($name, $email);
```

Narrow interface, invariant back with its owner, and no way to misuse it.

| From another module | Allowed |
|---|---|
| `Application/Services/` — a named use case | **yes**, this is the front door |
| `Domain/Events/`, `Domain/Contracts/`, `Domain/Traits/` | **yes**, meant to be shared (e.g. Audit's `LoggableModel`, `HasChangesLog`) |
| `Domain/Repositories/` or the Eloquent adapter | **no** — raw persistence, invariants bypassed |
| `Domain/Models/` for mutation | **no** |
| `Application/Commands/`, `Queries/` | **no** — internal to that module |

Prefer domain events when the other module doesn't need an answer. Call a service when you need a result back, like the id above.

**Returning across a boundary:** an id, a DTO, or `void` — in that order of preference. A model may cross **read-only, in specific cases**, because Filament reads relations across modules routinely (`offer.author.name` on a table column) and forbidding that would cost more than it protects. There is no mechanical guarantee here; the rule is that a model crossing a boundary is never mutated on the far side. If you find yourself calling `->update()` on a model from another module, the operation you actually needed is a method on that module's service.

## Filament trade-offs

Filament is the only UI surface, and two rules bend for it. Both are deliberate. Neither licenses anything further.

**1. Eloquent models are domain entities.** Domain entities are not framework-free. Mapping pure entities to models would cost Filament's search, filtering, relation managers and table sorting, all of which would then be hand-built. The trade is worth it — but the model is still driven through commands, never mutated from the UI.

**2. Domain enums may implement Filament contracts.** An enum in `Domain/Enums/` may implement `HasLabel`, `HasColor`, `HasIcon` and call `__()`. That keeps `Select::options(StatusEnum::class)` and `->badge()` resolving automatically instead of spawning a presenter class per enum.

The boundary: **these are the only UI imports allowed in Domain.** No `Heroicon` in business logic, no Filament types in method signatures, nothing else from `Filament\` in a domain class.

## Creating a module

```bash
dartisan make:module {Name}            # PascalCase, usually plural
dartisan make:module {Name} --singular={Entity}
```

Interactive prompts cover domain models, translations and the event provider. The command scaffolds directories, the service provider and starter files.

`{Name}ModuleServiceProvider` extends `App\Core\Providers\ModuleServiceProvider` and is configured by properties alone — `$eventProviders`, `$containerBindings`, `$loadsMigrations`. It already loads migrations, merges config, registers views, builds the morph map and boots event providers. Don't override `register()` or `boot()` without a genuine reason.

## Migration status

The layout above is the target. Existing code predates it — **where this skill and existing code disagree, this skill wins.**

Not yet migrated:

- **`make:module` and the Core abstracts match this skill.** A scaffolded module gets the repository port, its `Eloquent…` adapter, the container binding, `Domain/Policies/`, `Application/Listeners/`, `Application/Dto/` and observers under `Infrastructure/Persistence/Observers/`.
- **`App\Core\Abstracts\ModelRepository` is deprecated**, kept only until the two modules below are migrated. New adapters extend `Core\Abstracts\EloquentRepository` and implement a port extending `Core\Contracts\Repository`.
- **Users, Audit** — repositories are still concrete classes in `Domain/Repositories/`, with no port. Audit's listeners are already correctly in `Application/Listeners/`; its observers still need moving.
- Neither module has tests yet. See `module-testing`.
