<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence\Mappers;

use App\Modules\Catalogue\Domain\Entities\GlassModel;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use App\Modules\Catalogue\Domain\ValueObjects\OemCode;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelEurocodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelOemCodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Mappers\GlassModelMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlassModelMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_entity_survives_the_round_trip(): void
    {
        $mapper = new GlassModelMapper;
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create();
        $vehicle = VehicleModelFactory::new()->create();
        $model = GlassModelModelFactory::new()->fitting($vehicle)->withMainEurocode('8613AGS')->withColour($colour)->withEquipment($equipment)->withdrawn()->create();
        GlassModelEurocodeModelFactory::new()->create(['glass_model_id' => $model->id, 'code' => '8613XYZ']);
        GlassModelOemCodeModelFactory::new()->code('1K0-845')->create(['glass_model_id' => $model->id]);

        $entity = $mapper->toEntity($model->fresh());

        $this->assertEquals(GlassModel::reconstitute(
            $model->id,
            [Eurocode::from('8613AGS'), Eurocode::from('8613XYZ')],
            Eurocode::from('8613AGS'),
            $model->position_id,
            $colour->id,
            [$equipment->id],
            [OemCode::tryFrom('1K0-845')],
            true,
            [$vehicle->id],
        ), $entity);
        $this->assertSame(
            ['position_id' => $model->position_id, 'colour_id' => $colour->id, 'withdrawn' => true],
            $mapper->toRow($entity),
        );
        $this->assertSame(['8613AGS' => true, '8613XYZ' => false], $mapper->eurocodeRows($entity));
        $this->assertSame(['1K0845' => '1K0-845'], $mapper->oemCodeRows($entity));
    }
}
