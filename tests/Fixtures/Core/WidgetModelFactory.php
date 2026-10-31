<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

use App\Core\Infrastructure\Factories\ModelFactory;

/** @extends ModelFactory<WidgetModel> */
final class WidgetModelFactory extends ModelFactory
{
    protected $model = WidgetModel::class;

    public function definition(): array
    {
        return ['name' => fake()->word()];
    }
}
