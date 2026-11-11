<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence\Mappers;

use App\Modules\Catalogue\Domain\Entities\Vehicle;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleDetails;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleName;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Mappers\VehicleMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_full_entity_survives_the_round_trip(): void
    {
        $mapper = new VehicleMapper;
        $entity = Vehicle::reconstitute(7, VehicleName::from('AUDI | A4'), VehicleDetails::from('A4', 2008, 2015, 'B8', '0123'), 3, 4);

        $model = VehicleModelFactory::new()->make(['id' => 7] + $mapper->toRow($entity));

        $this->assertEquals($entity, $mapper->toEntity($model));
    }

    public function test_an_entity_with_only_a_make_survives_the_round_trip_and_carries_the_comparison_key(): void
    {
        $mapper = new VehicleMapper;
        $entity = Vehicle::reconstitute(8, VehicleName::from('Audi'), VehicleDetails::from(null, null, null, null, null), 3, null);

        $row = $mapper->toRow($entity);

        $this->assertSame('audi', $row['name_key']);
        $this->assertEquals($entity, $mapper->toEntity(VehicleModelFactory::new()->make(['id' => 8] + $row)));
    }
}
