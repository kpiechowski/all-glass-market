<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelOemCodeModel;

/** @extends ModelFactory<GlassModelOemCodeModel> */
final class GlassModelOemCodeModelFactory extends ModelFactory
{
    protected $model = GlassModelOemCodeModel::class;

    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->bothify('??#-###-###'));

        return [
            'glass_model_id' => GlassModelModelFactory::new(),
            'code' => $code,
            'code_key' => str_replace(['-', ' ', '.'], '', $code),
        ];
    }

    public function code(string $code): static
    {
        return $this->state(fn (): array => [
            'code' => $code,
            'code_key' => str_replace(['-', ' ', '.'], '', $code),
        ]);
    }
}
