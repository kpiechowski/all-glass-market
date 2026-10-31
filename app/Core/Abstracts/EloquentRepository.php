<?php

declare(strict_types=1);

namespace App\Core\Abstracts;

use App\Core\Contracts\Mapper;
use App\Core\Contracts\Repository;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent adapter base for repository ports. Entities expose their id as id().
 *
 * Concrete adapters live in a module's Infrastructure/Persistence/, implement
 * that module's port and pass model class and mapper up:
 *
 *   final class EloquentOfferRepository extends EloquentRepository implements OfferRepository
 *   {
 *       public function __construct(OfferMapper $mapper)
 *       {
 *           parent::__construct(OfferModel::class, $mapper);
 *       }
 *   }
 *
 * Writes go through fill()->save() inside the write scope, so timestamps,
 * dirty tracking and observers keep working.
 *
 * @template TEntity of object
 *
 * @implements Repository<TEntity>
 */
abstract class EloquentRepository implements Repository
{
    /**
     * @param  class-string<ReadOnlyModel>  $modelClass
     * @param  Mapper<TEntity>  $mapper
     */
    public function __construct(
        private readonly string $modelClass,
        private readonly Mapper $mapper,
    ) {}

    /**
     * Query entry point for adapter methods. Protected on purpose: the port
     * exposes named domain queries, never a builder.
     *
     * @return Builder<ReadOnlyModel>
     */
    protected function query(): Builder
    {
        return $this->modelClass::query();
    }

    public function get(int $id): object
    {
        return $this->mapper->toEntity($this->query()->findOrFail($id));
    }

    public function find(int $id): ?object
    {
        $model = $this->query()->find($id);

        return $model === null ? null : $this->mapper->toEntity($model);
    }

    public function add(object $entity): int
    {
        $model = new ($this->modelClass);

        ReadOnlyModel::writing(fn () => $model->fill($this->mapper->toRow($entity))->save());

        return $model->getKey();
    }

    public function save(object $entity): void
    {
        $model = $this->query()->findOrFail($entity->id());

        ReadOnlyModel::writing(fn () => $model->fill($this->mapper->toRow($entity))->save());
    }

    public function remove(object $entity): void
    {
        $model = $this->query()->findOrFail($entity->id());

        ReadOnlyModel::writing(fn () => $model->delete());
    }
}
