<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;

/** @extends ModelFactory<DictionaryValueModel> */
final class DictionaryValueModelFactory extends ModelFactory
{
    protected $model = DictionaryValueModel::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'dictionary' => Dictionary::Position,
            'name' => $name,
            'name_key' => mb_strtolower($name),
            'withdrawn' => false,
        ];
    }

    public function of(Dictionary $dictionary): static
    {
        return $this->state(fn (): array => ['dictionary' => $dictionary]);
    }

    public function named(string $name): static
    {
        return $this->state(fn (): array => ['name' => $name, 'name_key' => mb_strtolower($name)]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn (): array => ['withdrawn' => true]);
    }
}
