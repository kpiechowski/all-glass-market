---
name: module-cqrs
description: "Commands and handlers — the write path. Use when adding a user intent, writing a handler, deciding where validation or a permission check goes, writing a job, console command or ModuleApi method, or choosing between a command and a query."
---

# Commands and handlers

A **command** is one intent of an actor. Its **handler** is where the system decides and changes state. Every write in the application — from Filament, HTTP, console, a job, a listener or another module's `ModuleApi` — arrives as a command, so the rules hold no matter who sent it.

## Shape

```
Application/Commands/ActivateOffer/
  ActivateOfferCommand.php
  ActivateOfferCommandHandler.php
```

```php
final readonly class ActivateOfferCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
        public ?string $note = null,
    ) {}
}
```

```php
final class ActivateOfferCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
    ) {}

    #[CommandHandler]
    public function handle(ActivateOfferCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OfferPermission::Activate);

        $offer = $this->offers->get($command->offerId);
        $offer->activate();
        $this->offers->save($offer);

        event(new OfferActivated($offer->id(), CarbonImmutable::now()));
    }
}
```

- `{Verb}{Thing}Command` + `{Verb}{Thing}CommandHandler` in a folder named `{Verb}{Thing}`. Use the business verb when the intent has a name (`Activate`, `Reserve`, `Archive`); `Create` / `Update` / `Delete` only for plain data maintenance.
- One intent per command. A name that needs "And" is two commands.
- The command is `final readonly`, carries **primitives plus one `Actor`**, and holds no behaviour. `Actor` is the only object allowed in a command (ADR 0004).
- The handler has one public `handle` method with `#[CommandHandler]`, depends on **ports** (`OfferRepository`, `Authorizer`), and is auto-discovered by Ecotone.
- Every command runs inside a DB transaction opened by the Core bus interceptor. Write no `DB::transaction()` in a handler.

## The handler's sequence

Every handler follows the same order:

1. **Authorize:** `$this->authorizer->ensure($command->actor, {Name}Permission::X)`.
2. **Build value objects** from the primitives (`Email::from($command->email)`). A format error throws here. A string that becomes an enum goes through `tryFrom()` and a domain exception when it is null, so an unknown value is a refusal, never a `ValueError`.
3. **Gather facts** that need data: ask a port (`$this->users->emailIsTaken($email)`) or another module's `ModuleApi`.
4. **Decide in the domain:** call the entity's behaviour method, passing the facts in. The entity or a domain policy throws when the rule refuses.
5. **Persist** through the port: `add()` for new entities, `save()` for changed ones.
6. **Emit** the business event (`module-events`).

## Validation: five layers

| Layer | Catches | Where |
|---|---|---|
| 1. UI | convenience for the user | Filament rules, FormRequest — never the only guard |
| 2. Shape | missing field, wrong type | the command's typed constructor |
| 3. Format | malformed email, negative price, bad eurocode | value object constructor |
| 4. Invariant | "an offer without a price cannot be activated" | entity behaviour method; `{X}DomainPolicy` when the rule spans entities or the UI needs it |
| 5. Facts from data | unique email, vehicle exists, no active reservation | handler gathers the fact through a port and passes it in; a DB constraint backs it |

The domain never queries. The handler collects facts; the entity decides. An `if` that throws a refusal belongs in the entity or a domain policy, also when it sits in a handler or an `Application/Services/` class: the handler and the service fetch (a value, whether it exists), the entity judges (wrong dictionary, withdrawn, moved onto itself).

**A rule the UI also needs** (to show or hide an action) lives in `{X}DomainPolicy` as a pure method on **values**, and the entity calls it:

```php
final class OfferDomainPolicy
{
    public static function canBeActivated(OfferStatus $status, ?int $price): bool
    {
        return $status === OfferStatus::Draft && $price !== null;
    }
}

// in the entity
public function activate(): void
{
    if (! OfferDomainPolicy::canBeActivated($this->status, $this->price)) {
        throw OfferCannotBeActivated::because($this->id);
    }
    $this->status = OfferStatus::Active;
}
```

Filament calls the same method with the model's fields (`filament-module-wiring`). One rule, one source.

## Authorization

`Core\Contracts\Authorizer` answers "may this actor do this", implemented by the Users module. Modules check **permissions** from their own `{Name}Permission` enum, never roles.

- Handlers call `ensure()`, which throws.
- Filament calls `actorCan()` from the Core trait to show or hide (`filament-module-wiring`).
- `Actor::user($id)` represents a person. A job a person started carries that person's actor.
- `Actor::system('<reason>')` is for the scheduler, integration webhooks and listeners only. The authorizer lets it through and the reason reaches the audit log. UserInterface builds `Actor::user()` from the Core trait, always.

Laravel Policies and Gates are not used (ADR 0004).

## Errors

Domain exceptions extend `Core\Exceptions\DomainException` and carry a translation key from `{module}::exceptions` with raw parameters; translation happens in the UI, so the domain calls no `__()`. Throw them; the Core Filament trait turns them into a danger notification and keeps the form open. Anything else surfaces as an error.

### Logging

Handlers and jobs write to the Technical log; the channels and their label translation keys are in `config/logging.php` (`labels`).

- Log a failure with `Log::error()` and context: what failed, which record id, the external system's response. Never `info` for an error.
- Do not swallow an exception. Catch it only to log it and then rethrow, fail the job, or return a failed result.
- A job logs to `kolejka`, an integration to its own channel, a security event to `bezpieczenstwo`; everything else goes to the default `aplikacja`.
- Tell a user "szczegóły w logu" only when the failure went to a channel with a label.
- A new channel is a daily entry in `config/logging.php` (14 days) plus a label translation key from your module's lang files.

## Return values

| Handler | Returns | Filament page then |
|---|---|---|
| creates | `int` id | `return Offer::findOrFail($id);` |
| updates, deletes, acts | `void` | `return $record->refresh();` |

Entities and models never leave a handler.

## Reads

| Read | Path |
|---|---|
| Display in Filament (tables, forms, widgets) | `{X}Queries` method returning `Builder` (`module-persistence`) |
| Feeds a decision or calculation | `Application/Queries/{Verb}{Thing}/` with `#[QueryHandler]`, returning a DTO or scalar |

## Other entry points

- **`ModuleApi`** method that writes: builds this module's command and sends it on the bus, with the actor the caller passed in. Only other modules and integrations call `ModuleApi` (`module-layout`).
- **One intent built from another of the same module** (open a workshop together with its manager): one command for the whole intent; the shared part lives in `Application/Services/`, or the handler sends the other command on the bus, which runs nested in the same transaction.
- **Job** (`Application/Jobs/`): holds ids, primitives and the `Actor`; `handle()` sends one command.
- **Console command** (`UserInterface/Console/`): parses input, sends a command.
- **Internal service** (`Application/Services/`): logic shared by several handlers of this module; may use ports; called only from handlers.

## Before you finish

- Every write path you touched ends in a command sent on the bus.
- Every handler authorizes first and follows the six-step sequence.
- Every command holds only primitives and one `Actor`.
- Every rule lives in an entity method or a domain policy, and the handler and its services only gather facts.
- Every enum built from command input uses `tryFrom()` and refuses with a domain exception.
- Every handler has a test written before it, covering the happy path and each refusal (`module-testing`).
