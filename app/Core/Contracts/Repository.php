<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared CRUD surface every module repository port extends.
 *
 * Module ports add methods named in domain language:
 *
 *   interface OfferRepository extends Repository
 *   {
 *       public function activeForVehicle(int $vehicleId): Collection;
 *   }
 *
 * There is deliberately no query() here. A port handing out a query builder
 * guarantees nothing, because every caller could bypass it. Building queries
 * belongs inside the adapter.
 */
interface Repository
{
    public function find(int|string $id): ?Model;

    public function findOrFail(int|string $id): Model;

    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes): Model;

    public function delete(Model $model): bool;
}
