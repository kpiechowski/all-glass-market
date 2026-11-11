<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages\ListVehicles;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListVehiclesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_a_seller_cannot_open_the_list(): void
    {
        $this->actingAs(UserModel::factory()->create());

        Livewire::test(ListVehicles::class)->assertForbidden();
    }

    public function test_search_finds_a_vehicle_by_name_and_by_its_fields(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $make = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->named('Skoda')->create();
        $audi = VehicleModelFactory::new()->named('AUDI | A4')->create(['model' => 'A4', 'argic_code' => '4321']);
        $skoda = VehicleModelFactory::new()->ofMake($make->id)->named('Octavia')->create();

        Livewire::test(ListVehicles::class)
            ->assertCanSeeTableRecords([$audi, $skoda])
            ->searchTable('audi')
            ->assertCanSeeTableRecords([$audi])
            ->assertCanNotSeeTableRecords([$skoda])
            ->searchTable('4321')
            ->assertCanSeeTableRecords([$audi])
            ->searchTable('skoda')
            ->assertCanSeeTableRecords([$skoda])
            ->assertCanNotSeeTableRecords([$audi]);
    }

    public function test_it_filters_by_make_and_body(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $body = DictionaryValueModelFactory::new()->of(Dictionary::VehicleBody)->create();
        $withBody = VehicleModelFactory::new()->withBody($body->id)->create();
        $plain = VehicleModelFactory::new()->create();

        Livewire::test(ListVehicles::class)
            ->filterTable('make_id', $plain->make_id)
            ->assertCanSeeTableRecords([$plain])
            ->assertCanNotSeeTableRecords([$withBody])
            ->removeTableFilters()
            ->filterTable('body_id', $body->id)
            ->assertCanSeeTableRecords([$withBody])
            ->assertCanNotSeeTableRecords([$plain]);
    }

    public function test_an_admin_deletes_a_vehicle_from_the_row_menu(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $vehicle = VehicleModelFactory::new()->create();

        Livewire::test(ListVehicles::class)
            ->callAction(TestAction::make('delete')->table($vehicle))
            ->assertNotified();

        $this->assertNull($vehicle->fresh());
    }

    public function test_the_list_shows_how_many_glass_models_a_vehicle_fits(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $vehicle = VehicleModelFactory::new()->create();
        GlassModelModelFactory::new()->fitting($vehicle)->create();
        GlassModelModelFactory::new()->fitting($vehicle)->create();

        Livewire::test(ListVehicles::class)
            ->assertTableColumnStateSet('glass_models_count', 2, $vehicle);
    }

    public function test_deleting_a_fitted_vehicle_warns_with_the_count_and_removes_vehicle_and_fitments(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $vehicle = VehicleModelFactory::new()->create();
        $model = GlassModelModelFactory::new()->fitting($vehicle)->create();
        GlassModelModelFactory::new()->fitting($vehicle)->create();

        Livewire::test(ListVehicles::class)
            ->mountAction(TestAction::make('delete')->table($vehicle))
            ->assertMountedActionModalSee('modeli szkła: 2')
            ->callMountedAction()
            ->assertNotified();

        $this->assertNull($vehicle->fresh());
        $this->assertNotNull(GlassModelModel::find($model->id));
        $this->assertCount(0, $model->fresh()->vehicles);
    }
}
