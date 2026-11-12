<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelEurocodeModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;

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

    public function forGlassModel(GlassModelModel $glassModel): static
    {
        return $this->state(fn (): array => ['glass_model_id' => $glassModel->id]);
    }

    public function withCode(string $code): static
    {
        return $this->state(fn (): array => ['code' => $code]);
    }

    public function main(): static
    {
        return $this->state(fn (): array => ['is_main' => true]);
    }
}
