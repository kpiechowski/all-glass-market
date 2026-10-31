<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Shared surface of every module repository port, on entities.
 *
 * Module ports add methods named in domain language and return entities, ids
 * or scalars, never a Builder or a model:
 *
 *   interface OfferRepository extends Repository
 *   {
 *       public function hasActiveForVehicle(int $vehicleId): bool;
 *   }
 *
 * @template TEntity of object
 */
interface Repository
{
    /**
     * @return TEntity
     *
     * @throws ModelNotFoundException
     */
    public function get(int $id): object;

    /**
     * @return TEntity|null
     */
    public function find(int $id): ?object;

    /**
     * @param  TEntity  $entity
     * @return int the new id
     */
    public function add(object $entity): int;

    /**
     * @param  TEntity  $entity
     */
    public function save(object $entity): void;

    /**
     * @param  TEntity  $entity
     */
    public function remove(object $entity): void;
}
