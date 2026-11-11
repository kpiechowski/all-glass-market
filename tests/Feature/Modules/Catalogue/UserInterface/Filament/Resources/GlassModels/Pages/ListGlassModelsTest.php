<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelEurocodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelOemCodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages\ListGlassModels;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListGlassModelsTest extends TestCase
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

        Livewire::test(ListGlassModels::class)->assertForbidden();
    }

    public function test_an_admin_sees_the_models_with_eurocode_position_and_colour(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->withdrawn()->create();

        Livewire::test(ListGlassModels::class)
            ->filterTable('with_withdrawn', true)
            ->assertCanSeeTableRecords([$model])
            ->assertSee('8613AGS')
            ->assertSee($model->position->name)
            ->assertSee('Wycofany');
    }

    public function test_a_model_is_withdrawn_from_the_row_menu(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $model = GlassModelModelFactory::new()->create();

        Livewire::test(ListGlassModels::class)
            ->callAction(TestAction::make('withdraw')->table($model));

        $this->assertTrue($model->fresh()->withdrawn);
    }

    public function test_a_card_shows_every_eurocode_oem_code_parameters_and_the_vehicle_count(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->named('Zielony')->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->named('Czujnik deszczu')->create();
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->withColour($colour)->withEquipment($equipment)
            ->fitting(VehicleModelFactory::new()->create(), VehicleModelFactory::new()->create())->create();
        GlassModelEurocodeModelFactory::new()->state(['glass_model_id' => $model->id, 'code' => '9999ZZZ', 'is_main' => false])->create();
        GlassModelOemCodeModelFactory::new()->state(['glass_model_id' => $model->id])->code('1K0-845-011')->create();

        Livewire::test(ListGlassModels::class)
            ->assertSeeInOrder(['8613AGS', '9999ZZZ', '1K0-845-011', $model->position->name, 'Zielony', 'Czujnik deszczu'])
            ->assertSee('Pasuje do pojazdów');
    }

    public function test_withdrawn_models_are_hidden_until_asked_for(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $active = GlassModelModelFactory::new()->create();
        $withdrawn = GlassModelModelFactory::new()->withdrawn()->create();

        Livewire::test(ListGlassModels::class)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$withdrawn])
            ->filterTable('with_withdrawn', true)
            ->assertCanSeeTableRecords([$active, $withdrawn]);
    }

    public function test_the_search_orders_eurocode_oem_and_vehicle_hits_and_labels_each_card(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $vehicleHit = GlassModelModelFactory::new()->withMainEurocode('1000AAA')->fitting(VehicleModelFactory::new()->named('Audi 861 sedan')->create())->create();
        $oemHit = GlassModelModelFactory::new()->withMainEurocode('2000BBB')->create();
        GlassModelOemCodeModelFactory::new()->state(['glass_model_id' => $oemHit->id])->code('861-000')->create();
        $eurocodeHit = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        $miss = GlassModelModelFactory::new()->withMainEurocode('7777XXX')->create();

        Livewire::test(ListGlassModels::class)
            ->searchTable('861')
            ->assertCanSeeTableRecords([$eurocodeHit, $oemHit, $vehicleHit], inOrder: true)
            ->assertCanNotSeeTableRecords([$miss])
            ->assertSeeInOrder(['Eurokod: 8613AGS', 'Kod OEM: 861-000', 'Pojazd: Audi 861 sedan']);
    }

    public function test_a_search_shorter_than_the_minimum_finds_nothing(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();

        Livewire::test(ListGlassModels::class)
            ->searchTable('86')
            ->assertCanNotSeeTableRecords([$model]);
    }

    public function test_the_filters_narrow_the_search_and_the_plain_list(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $make = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->create();
        $wanted = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->withPosition($position)
            ->fitting(VehicleModelFactory::new()->ofMake($make->id)->create())->create();
        $other = GlassModelModelFactory::new()->withMainEurocode('8613BGS')->create();

        Livewire::test(ListGlassModels::class)
            ->filterTable('position_id', $position->id)
            ->assertCanSeeTableRecords([$wanted])
            ->assertCanNotSeeTableRecords([$other])
            ->searchTable('8613')
            ->assertCanSeeTableRecords([$wanted])
            ->assertCanNotSeeTableRecords([$other]);

        Livewire::test(ListGlassModels::class)
            ->filterTable('make_id', $make->id)
            ->assertCanSeeTableRecords([$wanted])
            ->assertCanNotSeeTableRecords([$other]);
    }
}
