<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages;

use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages\ViewVehicle;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewVehicleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_a_seller_cannot_open_a_vehicle(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $vehicle = VehicleModelFactory::new()->create();

        Livewire::test(ViewVehicle::class, ['record' => $vehicle->getKey()])->assertForbidden();
    }

    public function test_an_admin_sees_the_vehicle_and_deletes_it_from_the_page(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $vehicle = VehicleModelFactory::new()->named('AUDI | A4')->create(['year_from' => 2008, 'argic_code' => '1234']);

        Livewire::test(ViewVehicle::class, ['record' => $vehicle->getKey()])
            ->assertSee('AUDI | A4')
            ->assertSee('2008–')
            ->assertSee('1234')
            ->callAction('delete')
            ->assertRedirect();

        $this->assertNull(VehicleModel::find($vehicle->id));
    }
}
