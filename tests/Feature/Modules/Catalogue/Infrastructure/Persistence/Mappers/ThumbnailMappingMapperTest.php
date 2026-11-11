<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence\Mappers;

use App\Modules\Catalogue\Domain\Entities\ThumbnailMapping;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Domain\ValueObjects\FillColour;
use App\Modules\Catalogue\Infrastructure\Factories\ThumbnailMappingModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Mappers\ThumbnailMappingMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThumbnailMappingMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_kind_of_mapping_survives_the_round_trip(): void
    {
        $mapper = new ThumbnailMappingMapper;

        foreach ([
            ThumbnailMapping::reconstitute(7, 1, Dictionary::Position, GlassShape::SideFixed, true, null, null, null),
            ThumbnailMapping::reconstitute(8, 2, Dictionary::Colour, null, false, FillColour::from('#336699'), 25, null),
            ThumbnailMapping::reconstitute(9, 3, Dictionary::Equipment, null, false, null, null, Pictogram::Acoustic),
        ] as $entity) {
            $model = ThumbnailMappingModelFactory::new()->make(['id' => $entity->id()] + $mapper->toRow($entity));

            $this->assertEquals($entity, $mapper->toEntity($model));
        }
    }
}
