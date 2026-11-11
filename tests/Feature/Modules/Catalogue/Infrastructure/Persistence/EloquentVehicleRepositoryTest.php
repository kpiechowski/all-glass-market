<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence;

use App\Modules\Catalogue\Domain\Repositories\VehicleRepository;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentVehicleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_is_taken_ignores_case(): void
    {
        VehicleModelFactory::new()->named('Audi | A4')->create();
        $repository = app(VehicleRepository::class);

        $this->assertTrue($repository->nameIsTaken('AUDI | a4'));
        $this->assertFalse($repository->nameIsTaken('Audi | A6'));
    }

    public function test_name_is_taken_leaves_out_the_vehicle_being_changed(): void
    {
        $own = VehicleModelFactory::new()->named('Audi | A4')->create();
        $repository = app(VehicleRepository::class);

        $this->assertFalse($repository->nameIsTaken('audi | a4', $own->id));
        $this->assertTrue($repository->nameIsTaken('audi | a4', $own->id + 1));
    }
}
