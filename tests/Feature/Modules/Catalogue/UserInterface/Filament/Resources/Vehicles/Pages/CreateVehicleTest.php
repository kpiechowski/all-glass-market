<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages\CreateVehicle;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateVehicleTest extends TestCase
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

        Livewire::test(CreateVehicle::class)->assertForbidden();
    }

    public function test_an_admin_creates_a_vehicle_through_the_command(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $make = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->named('AUDI')->create();

        Livewire::test(CreateVehicle::class)
            ->fillForm(['make_id' => $make->id, 'model' => 'A4', 'year_from' => 2008, 'name' => 'AUDI | A4 | 2008-'])
            ->call('create')
            ->assertHasNoFormErrors();

        $vehicle = VehicleModel::firstOrFail();
        $this->assertSame('AUDI | A4 | 2008-', $vehicle->name);
        $this->assertSame($make->id, $vehicle->make_id);
        $this->assertSame(2008, $vehicle->year_from);
    }

    public function test_the_name_is_proposed_from_the_fields_and_follows_them_until_the_admin_types_their_own(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $make = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->named('AUDI')->create();
        $body = DictionaryValueModelFactory::new()->of(Dictionary::VehicleBody)->named('SEDAN')->create();

        Livewire::test(CreateVehicle::class)
            ->fillForm(['make_id' => $make->id])
            ->assertSchemaStateSet(['name' => 'AUDI'])
            ->fillForm(['model' => 'A4', 'body_id' => $body->id, 'generation' => 'B8', 'year_from' => 2008, 'argic_code' => '1234'])
            ->assertSchemaStateSet(['name' => 'AUDI | A4 | SEDAN | B8 2008- | 1234'])
            ->fillForm(['name' => 'Moja nazwa'])
            ->fillForm(['model' => 'A6'])
            ->assertSchemaStateSet(['name' => 'Moja nazwa']);
    }

    public function test_a_taken_name_is_shown_on_the_name_field(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        VehicleModelFactory::new()->named('Audi | A4')->create();
        $make = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->create();

        Livewire::test(CreateVehicle::class)
            ->fillForm(['make_id' => $make->id, 'name' => 'AUDI | a4'])
            ->call('create')
            ->assertHasFormErrors(['name']);

        $this->assertSame(1, VehicleModel::count());
    }

    public function test_a_withdrawn_make_is_not_offered(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $active = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->named('AUDI')->create();
        $withdrawn = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->named('STARA')->withdrawn()->create();

        Livewire::test(CreateVehicle::class)
            ->assertFormFieldExists('make_id', fn ($field): bool => array_key_exists($active->id, $field->getOptions())
                && ! array_key_exists($withdrawn->id, $field->getOptions()));
    }
}
