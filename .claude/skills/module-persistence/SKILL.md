---
name: module-persistence
description: "Data access in a module: repository ports and Eloquent adapters, models, migrations, factories, seeders, observers, and how Filament tables read through a repository. Use when adding or changing a model, writing a migration, creating or extending a repository, deciding where a query belongs, or wiring persistence into a module service provider."
---

# Module persistence

The domain states what it needs; infrastructure decides how. A repository is that split made concrete: an interface in `Domain/Repositories/`, an Eloquent implementation in `Infrastructure/Persistence/`.

For where files go generally, see `module-layout`. This skill covers what goes *inside* them.

## Port and adapter

The port keeps the domain name. The adapter gets the `Eloquent` prefix.

```php
// Domain/Repositories/OfferRepository.php — the port
interface OfferRepository extends Repository
{
    /** @return Collection<int, Offer> */
    public function activeForVehicle(int $vehicleId): Collection;
}
```

```php
// Infrastructure/Persistence/EloquentOfferRepository.php — the adapter
final class EloquentOfferRepository extends EloquentRepository implements OfferRepository
{
    protected function getModel(): string
    {
        return Offer::class;
    }

    public function activeForVehicle(int $vehicleId): Collection
    {
        return $this->query()
            ->where('vehicle_id', $vehicleId)
            ->where('status', OfferStatus::Active)
            ->get();
    }
}
```

Bind them in the module service provider:

```php
protected array $containerBindings = [
    OfferRepository::class => EloquentOfferRepository::class,
];
```

Handlers type-hint the port and never learn which implementation they got. That is what lets a test swap in a fake without a database.

### What belongs in the port

`Core\Contracts\Repository` covers the shared CRUD surface — `find`, `findOrFail`, `all`, `create`, `update`, `delete`. Module ports extend it and add methods named in domain language: `activeForVehicle`, `pendingReview`, `withExpiredReservation`.

**`query()` is not in the port.** It stays `protected` on `EloquentRepository`, available while writing an adapter and invisible to everyone else. A port handing out a query builder is not a port — every caller could bypass it and assemble arbitrary queries, and the interface would guarantee nothing.

The one deliberate exception is read queries for Filament, below. Those return a `Builder`, but under a *named* method that the domain chose to expose — not a general-purpose escape hatch.

### Naming queries

Name what it means, not how it filters. `pendingReview()` survives a change in how "pending" is stored; `whereStatusIsNullAndFlagIsTrue()` does not. Domain-specific query methods belong on the repository, never on the model as a scope — a scope is reachable from anywhere, which is exactly what the port prevents.

## Models

Eloquent models are domain entities here — a deliberate trade-off documented in `module-layout`. Practical consequences:

- Models live in `Domain/Models/` and may hold casts, relations, accessors and domain methods.
- **No query scopes for domain concepts.** They belong on the repository.
- **No business logic in lifecycle hooks.** Don't resolve services in `booted()`.
- Mutation happens through command handlers via the repository. UI never calls `->save()` or `->update()`.
- Enums used by the model may implement Filament's `HasLabel` / `HasColor` / `HasIcon` — the second documented trade-off.

Migrations, factories and seeders are infrastructure: `Infrastructure/migrations/`, `Infrastructure/Factories/`, `Infrastructure/Seeders/`. `ModuleServiceProvider` loads migrations when `$loadsMigrations = true`. Every model gets a factory — tests depend on it (`module-testing`).

Run migrations with `dartisan migrate`, never `php artisan`.

## Observers

An observer is an adapter between the ORM lifecycle and the domain, so it lives in `Infrastructure/Persistence/Observers/` and stays thin: **emit a domain event, nothing more.** The listener does the work (`module-events`).

```php
public function created(Offer $offer): void
{
    OfferCreated::dispatch($offer->id);
}
```

Anything heavier — resolving a service, writing to another table, calling an API — makes business logic untestable by welding it to `save()`. Register observers in the module's event service provider via `$observers`.

## Reading for Filament

Filament tables build their own query so that sorting, filtering and search keep working. That query can start from the repository, which keeps read access going through one place without losing anything Filament gives you.

Expose a named read method returning a `Builder`:

```php
// Domain/Repositories/OfferRepository.php
public function visibleForPanel(): Builder;
```

```php
// UserInterface/Filament/Resources/Offers/Tables/OffersTable.php
public static function configure(Table $table): Table
{
    return $table
        ->query(fn (OfferRepository $repository): Builder => $repository->visibleForPanel())
        ->columns([...]);
}
```

Filament layers its filters, sorts and search on top. A `Resource` can do the same by overriding `getEloquentQuery()`.

**This is a third documented Filament trade-off, alongside the two in `module-layout`:** reading for display may go straight to the repository, skipping the query bus. Given this application's size, a query handler that only forwards to a repository is ceremony. The boundary is unchanged and absolute:

- **Read for display** — repository directly from the Filament class. Fine.
- **Anything that changes state** — command bus. No exceptions.
- **Read feeding a decision or a calculation** — query bus, so the logic is testable outside the UI.

A Filament class may call a repository's read method. It may never call `create`, `update` or `delete` on one.

## Migration status

The target above is not yet reality. **Where this skill and existing code disagree, this skill wins.**

- **`Core\Contracts\Repository`, `Core\Abstracts\EloquentRepository` and `make:module` are done** — a scaffolded module gets the port, the `Eloquent…` adapter, observers under `Infrastructure/Persistence/Observers/` and the binding already wired.
- **`Core\Abstracts\ModelRepository` is deprecated** and kept only until the two modules below are migrated. Never extend it in new code.
- **Users and Audit** still have concrete repositories in `Domain/Repositories/` extending `ModelRepository`, with no port and no binding. Because the port keeps the domain name, migrating them moves files and adds bindings without touching a single handler.
