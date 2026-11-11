<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;

/**
 * A Glass model with its main Eurocode and a position. Colour and equipment are given by states;
 * further Eurocodes and OEM codes by `GlassModelEurocodeModelFactory` and `GlassModelOemCodeModelFactory`.
 *
 * @extends ModelFactory<GlassModelModel>
 */
final class GlassModelModelFactory extends ModelFactory
{
    protected $model = GlassModelModel::class;

    /** Shared by every instance of one chain, so the callback registered in `configure()` sees what the states ask for. */
    private ?\stdClass $options = null;

    public function definition(): array
    {
        return [
            'position_id' => fn (): int => DictionaryValueModelFactory::new()->of(Dictionary::Position)->create()->id,
            'colour_id' => null,
            'withdrawn' => false,
        ];
    }

    public function withMainEurocode(string $code): static
    {
        $this->options()->mainEurocode = $code;

        return $this;
    }

    public function withPosition(DictionaryValueModel $position): static
    {
        return $this->state(fn (): array => ['position_id' => $position->id]);
    }

    public function withColour(DictionaryValueModel $colour): static
    {
        return $this->state(fn (): array => ['colour_id' => $colour->id]);
    }

    public function withEquipment(DictionaryValueModel ...$equipment): static
    {
        $this->options()->equipmentIds = array_map(fn (DictionaryValueModel $value): int => $value->id, $equipment);

        return $this;
    }

    public function fitting(VehicleModel ...$vehicles): static
    {
        $this->options()->vehicleIds = array_map(fn (VehicleModel $vehicle): int => $vehicle->id, $vehicles);

        return $this;
    }

    public function withdrawn(): static
    {
        return $this->state(fn (): array => ['withdrawn' => true]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (GlassModelModel $model): void {
            GlassModelEurocodeModelFactory::new()->state([
                'glass_model_id' => $model->id,
                'code' => $this->options()->mainEurocode ?? strtoupper(fake()->unique()->bothify('????####')),
                'is_main' => true,
            ])->create();

            if ($this->options()->vehicleIds !== []) {
                $model->vehicles()->attach($this->options()->vehicleIds);
            }

            if ($this->options()->equipmentIds !== []) {
                $model->equipment()->attach($this->options()->equipmentIds);
            }
        });
    }

    protected function newInstance(array $arguments = []): static
    {
        $new = parent::newInstance($arguments);
        $new->options = $this->options();

        return $new;
    }

    private function options(): \stdClass
    {
        return $this->options ??= (object) ['mainEurocode' => null, 'equipmentIds' => [], 'vehicleIds' => []];
    }
}
