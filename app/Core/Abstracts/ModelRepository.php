<?php

declare(strict_types=1);

namespace App\Core\Abstracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent-backed base repository.
 *
 * Concrete repositories only need to declare the model class:
 *
 *   protected function getModel(): string
 *   {
 *       return Post::class;
 *   }
 *
 * All methods return the concrete model type due to Eloquent's generic support.
 */
abstract class ModelRepository
{
    private Model $model;

    public function __construct()
    {
        $this->model = app()->make($this->getModel());
    }

    /**
     * Entry point for custom queries. Use in subclasses or service layer:
     *
     *   $this->query()->where('active', true)->get();
     */
    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    abstract protected function getModel(): string;

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

    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

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
