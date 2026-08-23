---
name: module-events
description: "Domain events, listeners and communication between modules. Use when emitting an event, writing a listener, wiring an event service provider, or working out how one module should react to something happening in another — including cross-cutting concerns like audit logging and integrations reacting to domain changes."
---

# Events and cross-module communication

Events are how a module says what happened without knowing who cares. That is what keeps modules from importing each other.

For what one module may call directly, see `module-layout`. For handlers, `module-cqrs`.

## Emitting

Domain events live in `Domain/Events/` and are `readonly`, past-tense, and named for a business fact: `OfferActivated`, `UserRegistered`, `ReservationExpired`.

```php
readonly class OfferActivated
{
    public function __construct(
        public int $offerId,
        public CarbonImmutable $activatedAt,
    ) {}
}
```

**Carry ids and values, not models.** A listener that receives a model can mutate it; one that receives an id must go through a repository, which keeps the write path single. It also survives an event becoming asynchronous later.

Emit from the command handler that performed the change — that is where the business fact is known:

```php
#[CommandHandler]
public function handle(ActivateOfferCommand $command): void
{
    // …
    OfferActivated::dispatch($offer->id, CarbonImmutable::now());
}
```

An observer may emit too, but only that (`module-persistence`).

## Listening

Listeners live in `Application/Listeners/` — reacting to an event is orchestration, not a domain rule. Wire them in the module's event service provider:

```php
class OffersEventServiceProvider extends CoreEventServiceProvider
{
    protected $listen = [
        OfferActivated::class => [NotifyWatchersListener::class],
    ];

    protected $observers = [
        Offer::class => OfferObserver::class,
    ];
}
```

Register the provider in `{Name}ModuleServiceProvider::$eventProviders`.

A listener does one thing. Two unrelated reactions to one event are two listeners — that keeps failures isolated and names honest.

## Which module owns the event?

The module where the fact occurred. `OfferActivated` belongs to Offers even though Audit and the Otomoto integration both react to it. Listening never transfers ownership.

Consumers import the event class from the owning module's `Domain/Events/`, which is explicitly allowed. Nothing else about the emitting module may be touched.

## Event or direct call?

| Situation | Use |
|---|---|
| Something happened; whoever cares may react | **event** |
| You need a result back before continuing | **the other module's application service** |
| A reaction must be part of the same transaction and must not silently fail | **service**, so the failure surfaces |

Events are fire-and-forget by design. If you find yourself needing to know whether the listener succeeded, you wanted a call, not an event.

Ecotone runs synchronously here — no async configuration — so listeners execute in the same request and transaction as the dispatch.

## Cross-cutting concerns

A module that reacts to *any* module — audit, notifications — must not enumerate every event in the system. It would need a listener per module and a change every time one is added.

**Define the shared event in `Core/`, have modules emit it, and let the cross-cutting module listen to that one event.** The emitting module never learns who is listening; the listening module never learns which modules exist.

Per-model detail is carried by a contract the model implements, not by the event payload. Audit's `LoggableModel` is the existing example: it lets each model say how it presents itself (`getLoggableTitle`, `getLoggableResourceName`, `getLoggableUrl`, `getLoggableIcon`) while the event stays generic.

Keep events specific and directly wired where the coupling is intended — an integration reacting to `OfferSold` should name that event, because it genuinely only cares about that fact.

**When the source module doesn't exist yet**, leave the listener registration commented with a marker naming the phase, as `AuditEventServiceProvider` does. Do not use `class_exists()` guards: a conditional registration turns a typo into silence.

## Open question: how Audit decides what to log

Unresolved, to be settled when the Audit module is reworked. `LoggableModel` covers presentation but not:

- which fields are logged and which are skipped (passwords, tokens)
- how a relation's value is rendered rather than logged as a raw id
- relations not backed by an `_id` column on the model's own table

These are extensions to the contract, not a different architecture. Whoever reworks Audit decides them; don't invent an answer in passing.

## Migration status

- **`HasChangesLog` resolves a service inside `static::created()`**, which is the pattern this skill and `module-persistence` forbid. It is slated to change: the trait will emit a shared event and an Audit listener will write the entry. Until then, don't copy the pattern into new code.
- **No shared `Core/` event exists yet.** It arrives with the Audit rework above.
- **`AuditEventServiceProvider`** has its listeners commented out pending the Offers module. That is the intended marker style.
