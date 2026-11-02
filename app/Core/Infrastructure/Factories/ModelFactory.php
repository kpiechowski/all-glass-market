<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Factories;

use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for module model factories: create() opens the write scope the
 * read-only models require.
 *
 * @template TModel of Model
 *
 * @extends Factory<TModel>
 */
abstract class ModelFactory extends Factory
{
    public function create($attributes = [], ?Model $parent = null)
    {
        return ReadOnlyModel::writing(fn () => parent::create($attributes, $parent));
    }
}
