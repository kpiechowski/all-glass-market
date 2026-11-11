<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages\EditVehicle;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditVehicleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_a_seller_cannot_open_the_form(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $vehicle = VehicleModelFactory::new()->create();

        Livewire::test(EditVehicle::class, ['record' => $vehicle->getKey()])->assertForbidden();
    }

    public function test_an_admin_edits_a_vehicle_through_the_command_and_the_name_is_not_rewritten(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $vehicle = VehicleModelFactory::new()->named('Moja nazwa')->create();

        Livewire::test(EditVehicle::class, ['record' => $vehicle->getKey()])
            ->fillForm(['model' => 'Nowy model', 'year_from' => 2001, 'year_to' => 2003])
            ->assertSchemaStateSet(['name' => 'Moja nazwa'])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $vehicle->fresh();
        $this->assertSame('Moja nazwa', $fresh->name);
        $this->assertSame('Nowy model', $fresh->model);
        $this->assertSame(2003, $fresh->year_to);
    }

    public function test_a_withdrawn_make_on_the_vehicle_stays_offered_and_survives_the_save(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $withdrawn = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->withdrawn()->create();
        $other = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->withdrawn()->create();
        $vehicle = VehicleModelFactory::new()->ofMake($withdrawn->id)->create();

        Livewire::test(EditVehicle::class, ['record' => $vehicle->getKey()])
            ->assertFormFieldExists('make_id', fn ($field): bool => array_key_exists($withdrawn->id, $field->getOptions())
                && ! array_key_exists($other->id, $field->getOptions()))
            ->fillForm(['model' => 'Zmieniony'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($withdrawn->id, $vehicle->fresh()->make_id);
        $this->assertSame('Zmieniony', $vehicle->fresh()->model);
    }

    public function test_the_years_out_of_order_are_refused_and_nothing_is_saved(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $vehicle = VehicleModelFactory::new()->create();

        Livewire::test(EditVehicle::class, ['record' => $vehicle->getKey()])
            ->fillForm(['year_from' => 2010, 'year_to' => 2008])
            ->call('save');

        $this->assertNull($vehicle->fresh()->year_from);
    }
}
