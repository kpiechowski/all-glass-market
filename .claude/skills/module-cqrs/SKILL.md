---
name: module-cqrs
description: "Commands, queries and handlers in a module. Use when adding a user intent (create, update, delete, activate…), writing a command or query handler, deciding between a command and an application service, or working out where validation and business rules belong. Covers Ecotone buses, handler return values, and calling one module from another."
---

# Commands and queries

A command is a user intent. A query answers a question. Both are dispatched on a bus and handled by a class that does the work.

For where the files go, see `module-layout`. For repositories, `module-persistence`.

## Shape

```
Application/Commands/ActivateOffer/
  ActivateOfferCommand.php
  ActivateOfferCommandHandler.php
```

```php
readonly class ActivateOfferCommand
{
    public function __construct(
        public int $offerId,
        public ?string $note = null,
    ) {}
}
```

```php
class ActivateOfferCommandHandler
{
    public function __construct(
        private OfferRepository $offers,
        private OfferDomainPolicy $policy,
    ) {}

    #[CommandHandler]
    public function handle(ActivateOfferCommand $command): void
    {
        $offer = $this->offers->findOrFail($command->offerId);

        if (! $this->policy->canBeActivated($offer)) {
            throw new OfferCannotBeActivatedException();
        }

        $this->offers->update($offer, ['status' => OfferStatus::Active]);
    }
}
```

Rules:

- Commands and queries are `readonly` and carry data only — no behaviour, no defaults hiding logic.
- Named `{Verb}{Subject}Command` / `{Verb}{Subject}Query`, in a directory of the same name.
- One public `handle` method, attributed `#[CommandHandler]` or `#[QueryHandler]`.
- Dependencies via constructor. Handlers depend on repository **ports**, never adapters.
- Ecotone auto-discovers anything under `app/` — no registration.

**One command per user intent, not per CRUD verb.** "Activate an offer" is an intent. "Set status column" is a step inside one. If a command's name needs "And" to be accurate, it is two commands or one intent you have not named properly yet.

## Return values

No uniform rule — return what the caller genuinely needs.

- **`void`** when nothing needs anything back. The default.
- **An id** when the caller must reference the result, as when one module creates something for another.
- **A model** where it is required or plainly convenient — notably Filament's `handleRecordCreation()` and `handleRecordUpdate()`, whose contracts demand a `Model`.

Returning a model is fine inside its own module. Across a module boundary, prefer an id or DTO; see `module-layout`.

## Dispatching

Filament pages and Livewire components use the Core traits:

```php
use App\Core\Concerns\HasCommandBus;

class EditOffer extends EditRecord
{
    use HasCommandBus;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->commandBus->send(new UpdateOfferCommand($record->id, ...$data));
    }
}
```

Livewire calls `boot{TraitName}()` itself, so `$this->commandBus` and `$this->queryBus` are ready without constructor injection. Elsewhere, inject `CommandBus` / `QueryBus` normally.

**Every write path dispatches a command — including actions.** Page handlers are the obvious case, but a `DeleteAction`, bulk action or custom modal action writes to the database by itself unless redirected with `->using()` or `->action()`. See `filament-module-ui` for the patterns. A Filament class never calls `->save()`, `->update()`, `->delete()` or `->create()` on a model.

## Command, query, or application service?

**Commands and queries are the entry point for user interaction.** A form submits, a table action fires, a page loads — that is a command or a query.

**Application services handle work that no user directly triggered:** operations derived from another operation, background processing, multi-step orchestration called from several handlers, and the front door another module calls (`module-layout`).

The distinction that matters is what a service does inside:

- **A service exposed to other modules dispatches a command.** `createUserForBusinessOwning()` goes through `CreateUserCommand` like any other user creation — same events, same rules, one write path. The service adds the named operation and closes the invariant (the role); it does not reimplement the write.
- **A service used inside your own handler may call repositories directly.** It is already past the bus; going round again buys nothing.

Never let a service become a second write path that skips the rules a command enforces. That is the same failure as reaching for another module's repository, one level up.

Queries are worth the ceremony when a read feeds a decision or a calculation. A read that only fills a Filament table can go straight to the repository — see the read boundary in `module-persistence`.

## Validation and business rules

**If Livewire stopped working tomorrow and anything could be posted anywhere, the application must still hold.** Filament's `->required()` and `->email()` are there to help the user, never to protect the system. Treat UI validation as a hint and nothing more.

Three layers, each with one job:

| Layer | Catches | Where |
|---|---|---|
| **Shape** | missing field, wrong type | the command's `readonly` typed constructor |
| **Format** | malformed email, negative price, bad eurocode | value objects in `Domain/ValueObjects/`, or explicit checks in the handler |
| **Business rule** | "an offer without a price cannot be activated" | `{Name}DomainPolicy` in `Domain/Policies/`, asked by the handler |

The handler asks the policy before acting and throws a domain exception when it refuses. That way the rule holds no matter who dispatched the command — a Filament page, another module's service, or a test.

Domain exceptions live in `Domain/Exceptions/`. Let them surface; Filament renders them.

## Migration status

- **Users and Audit** handlers type-hint concrete repositories, because the ports do not exist yet (`module-persistence`). Once the port keeps the domain name, the type-hints stay as they are.
- **No domain policies exist yet.** Rules currently live inline in handlers. New work uses `Domain/Policies/`.
- **`make:module`** does not scaffold `Domain/Policies/`; create it by hand.
