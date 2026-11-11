<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;

/** @extends ModelFactory<VehicleModel> */
final class VehicleModelFactory extends ModelFactory
{
    protected $model = VehicleModel::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'name_key' => mb_strtolower($name),
            'make_id' => DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake),
            'model' => null,
            'body_id' => null,
            'year_from' => null,
            'year_to' => null,
            'generation' => null,
            'argic_code' => null,
        ];
    }

    public function named(string $name): static
    {
        return $this->state(fn (): array => ['name' => $name, 'name_key' => mb_strtolower($name)]);
    }

    public function ofMake(int $makeId): static
    {
        return $this->state(fn (): array => ['make_id' => $makeId]);
    }

    public function withBody(int $bodyId): static
    {
        return $this->state(fn (): array => ['body_id' => $bodyId]);
    }
}
