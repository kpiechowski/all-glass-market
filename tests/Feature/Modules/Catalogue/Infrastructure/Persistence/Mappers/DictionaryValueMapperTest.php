<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence\Mappers;

use App\Modules\Catalogue\Domain\Entities\DictionaryValue;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\ValueObjects\DictionaryValueName;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Mappers\DictionaryValueMapper;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DictionaryValueMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_entity_survives_the_round_trip(): void
    {
        $mapper = new DictionaryValueMapper;
        $entity = DictionaryValue::reconstitute(7, Dictionary::VehicleBody, DictionaryValueName::from('Kombi'), true);

        $model = DictionaryValueModelFactory::new()->make(['id' => 7] + $mapper->toRow($entity));

        $this->assertInstanceOf(DictionaryValueModel::class, $model);
        $this->assertEquals($entity, $mapper->toEntity($model));
    }

    public function test_the_row_carries_the_lowercased_comparison_key(): void
    {
        $entity = DictionaryValue::create(Dictionary::Colour, DictionaryValueName::from('Zielony'), false);

        $this->assertSame('zielony', (new DictionaryValueMapper)->toRow($entity)['name_key']);
    }
}
