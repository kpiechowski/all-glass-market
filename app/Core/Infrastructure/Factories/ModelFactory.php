<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Factories;

use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * Base for module model factories: create() opens the write scope the
 * read-only models require. Factories are test and seed tooling, never a
 * write path of the application, so outside a command they record their
 * changes as `Actor::system('factory')`. Inside a command (or a test that
 * sets an Actor) the current Actor is kept.
 *
 * @template TModel of Model
 *
 * @extends Factory<TModel>
 */
abstract class ModelFactory extends Factory
{
    public function create($attributes = [], ?Model $parent = null)
    {
        $actors = app(ActorStack::class);
        $create = fn () => ReadOnlyModel::writing(fn () => parent::create($attributes, $parent));

        return $actors->hasCurrent() ? $create() : $actors->run(Actor::system('factory'), $create);
    }
}
