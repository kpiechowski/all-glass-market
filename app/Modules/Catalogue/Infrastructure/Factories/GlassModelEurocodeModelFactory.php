<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelEurocodeModel;

/** @extends ModelFactory<GlassModelEurocodeModel> */
final class GlassModelEurocodeModelFactory extends ModelFactory
{
    protected $model = GlassModelEurocodeModel::class;

    public function definition(): array
    {
        return [
            'glass_model_id' => GlassModelModelFactory::new(),
            'code' => strtoupper(fake()->unique()->bothify('????####')),
            'is_main' => false,
        ];
    }
}
