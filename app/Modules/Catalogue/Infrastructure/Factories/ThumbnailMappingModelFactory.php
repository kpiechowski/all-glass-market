<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Factories;

use App\Core\Infrastructure\Factories\ModelFactory;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\ThumbnailMappingModel;

/** @extends ModelFactory<ThumbnailMappingModel> */
final class ThumbnailMappingModelFactory extends ModelFactory
{
    protected $model = ThumbnailMappingModel::class;

    public function definition(): array
    {
        return [
            'dictionary_value_id' => DictionaryValueModelFactory::new()->of(Dictionary::Position),
            'dictionary' => Dictionary::Position,
            'shape' => GlassShape::Windscreen,
            'mirrored' => false,
            'fill' => null,
            'opacity' => null,
            'pictogram' => null,
        ];
    }

    public function forPosition(DictionaryValueModel $value, GlassShape $shape, bool $mirrored = false): static
    {
        return $this->state(fn (): array => [
            'dictionary_value_id' => $value->id,
            'dictionary' => Dictionary::Position,
            'shape' => $shape,
            'mirrored' => $mirrored,
        ]);
    }

    public function forColour(DictionaryValueModel $value, string $fill, int $opacity): static
    {
        return $this->state(fn (): array => [
            'dictionary_value_id' => $value->id,
            'dictionary' => Dictionary::Colour,
            'shape' => null,
            'fill' => $fill,
            'opacity' => $opacity,
        ]);
    }

    public function forEquipment(DictionaryValueModel $value, Pictogram $pictogram): static
    {
        return $this->state(fn (): array => [
            'dictionary_value_id' => $value->id,
            'dictionary' => Dictionary::Equipment,
            'shape' => null,
            'pictogram' => $pictogram,
        ]);
    }
}
