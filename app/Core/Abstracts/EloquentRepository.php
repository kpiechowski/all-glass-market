<?php

declare(strict_types=1);

namespace App\Core\Abstracts;

use App\Core\Contracts\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent adapter base for repository ports.
 *
 * Concrete adapters live in a module's Infrastructure/Persistence/, implement
 * that module's port and only declare the model class:
 *
 *   final class EloquentOfferRepository extends EloquentRepository implements OfferRepository
 *   {
 *       protected function getModel(): string
 *       {
 *           return Offer::class;
 *       }
 *   }
 *
 * Bind port to adapter in the module service provider's $containerBindings.
 */
abstract class EloquentRepository implements Repository
{
    private Model $model;

    public function __construct()
    {
        $this->model = app()->make($this->getModel());
    }

    abstract protected function getModel(): string;

    /**
     * Query entry point for adapter methods. Protected on purpose: the port
     * exposes named domain queries, never a builder.
     */
    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    public function find(int|string $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model->refresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }
}
