---
name: module-persistence
description: "Entities, mappers, repositories and Eloquent models. Use when adding or changing an entity, model, migration, factory, repository method or mapper, writing a {X}Queries read for Filament, or deciding what belongs inside one aggregate."
---

# Persistence

The domain works on **entities**; Eloquent stays for what Laravel and Filament do well: reading, relations, search, filters. Each aggregate therefore has four pieces (ADR 0001):

| Piece | Place | Job |
|---|---|---|
| Entity | `Domain/Entities/{X}` | state, behaviour, invariants — plain PHP |
| Port | `Domain/Repositories/{X}Repository` | what the domain needs, on entities |
| Model | `Infrastructure/Persistence/Models/{X}` | Eloquent, **read-only** outside the adapter |
| Mapper + adapter | `Infrastructure/Persistence/Mappers/{X}Mapper`, `Infrastructure/Persistence/Eloquent{X}Repository` | translate and persist |

## Entity

```php
final class Offer
{
    private function __construct(
        private ?int $id,
        private OfferStatus $status,
        private ?int $price,
        private int $vehicleId,
    ) {}

    public static function draft(int $vehicleId, ?int $price): self
    {
        return new self(null, OfferStatus::Draft, $price, $vehicleId);
    }

    /** @internal for the mapper */
    public static function reconstitute(int $id, OfferStatus $status, ?int $price, int $vehicleId): self
    {
        return new self($id, $status, $price, $vehicleId);
    }

    public function activate(): void { /* guards via OfferDomainPolicy, see module-cqrs */ }

    public function id(): ?int { return $this->id; }
    public function status(): OfferStatus { return $this->status; }
}
```

- Private state, a named constructor per way of coming into existence, `reconstitute()` for the mapper.
- Behaviour methods named by intent (`activate`, `reserveUntil`) change state and throw domain exceptions; there are no general setters.
- Getters exist for what the mapper and handlers read.
- `?int $id` stays `null` until the repository's `add()` assigns it. Ids are autoincrement ints.
- No Eloquent, no facades, no I/O.

## Aggregates: keep them small

An aggregate is the entity plus the parts always loaded and saved with it. A part goes **inside** the parent entity only when all three hold: it cannot exist without the parent, it is never edited on its own, and there are few of them (order lines). Otherwise it is its own entity with its own repository, holding the parent's id (Reservation holds `offerId`). Rules across them are facts the handler gathers (`$reservations->hasActiveFor($offerId)`).

## Port

```php
interface OfferRepository extends Repository
{
    public function hasActiveForVehicle(int $vehicleId): bool;
}
```

`Core\Contracts\Repository` gives every port `get(int $id)` (throws when missing), `find(int $id): ?X`, `add(X): int`, `save(X): void`, `remove(X): void`. Module ports add methods named in domain language (`pendingReview`, not `whereStatusNull`), returning entities, ids or scalars, never a `Builder` or a model.

## Model

```php
final class OfferModel extends ReadOnlyModel   // in Infrastructure/Persistence/Models/
{
    protected $table = 'offers';
    protected function casts(): array { return ['status' => OfferStatus::class]; }
    public function vehicle(): BelongsTo { return $this->belongsTo(VehicleModel::class); }
}
```

- Extends `Core\Infrastructure\Persistence\ReadOnlyModel`: `save()`, `update()`, `delete()` throw unless called inside the write scope that the adapter and the Core base factory open.
- Holds casts, relations (also to other modules' models) and accessors for display. Domain rules live on the entity.
- No query scopes: named reads go to `{X}Queries`.
- Every model has a factory in `Infrastructure/Factories/` extending the Core base factory, with states for the cases tests need.

## Mapper and adapter

```php
/** @implements Mapper<Offer> */
final class OfferMapper implements Mapper   // Core\Contracts\Mapper
{
    public function toEntity(OfferModel $model): Offer
    {
        return Offer::reconstitute($model->id, $model->status, $model->price, $model->vehicle_id);
    }

    /** @return array<string, mixed> */
    public function toRow(Offer $offer): array
    {
        return ['status' => $offer->status(), 'price' => $offer->price(), 'vehicle_id' => $offer->vehicleId()];
    }
}
```

```php
final class EloquentOfferRepository extends EloquentRepository implements OfferRepository
{
    public function __construct(OfferMapper $mapper) { parent::__construct(OfferModel::class, $mapper); }

    public function hasActiveForVehicle(int $vehicleId): bool
    {
        return $this->query()->where('vehicle_id', $vehicleId)->where('status', OfferStatus::Active)->exists();
    }
}
```

`EloquentRepository` implements the shared methods: it loads the model, maps it, and writes with `$model->fill($mapper->toRow($entity))->save()` inside the write scope, so timestamps, dirty tracking and observers keep working. `query()` stays protected. A part held inside an aggregate is synced by id in the adapter.

Bind the port in the module provider:

```php
protected array $containerBindings = [
    OfferRepository::class => EloquentOfferRepository::class,
];
```

## Reads for display: `{X}Queries`

Filament needs a `Builder` so its sorting, search and filters keep working. Named display reads live in a concrete class:

```php
final class OfferQueries   // Infrastructure/Persistence/Queries/
{
    public function activeForSeller(Actor $actor): Builder
    {
        return OfferModel::query()->where('seller_id', $actor->id)->where('status', OfferStatus::Active);
    }
}
```

No interface, since an interface returning an Eloquent `Builder` abstracts nothing. Tables, resources (`getEloquentQuery()`) and widgets call it. This is the read trade-off of ADR 0002: display reads skip the query bus; writes never do.

## Migrations

`Infrastructure/migrations/`, run with `dartisan migrate`. Schemas need not mirror V1 but must be migratable from V1 data. Back every layer-5 rule with a constraint (unique, foreign key).

## Before you finish

- Every entity is plain PHP and changes state only through behaviour methods.
- Every port returns entities, ids or scalars.
- Every model extends `ReadOnlyModel`, has a factory, and is written only by its adapter.
- Every display read is a named `{X}Queries` method.
- Mapper, port methods and `{X}Queries` methods have tests written first, and the mapper's covers the round-trip (`module-testing`).
