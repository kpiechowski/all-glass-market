<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Pages;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Pages\ListDictionaryValues;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListDictionaryValuesTest extends TestCase
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

        Livewire::test(ListDictionaryValues::class)->assertForbidden();
    }

    public function test_each_tab_lists_one_dictionary_and_withdrawn_values_can_be_filtered(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $withdrawnColour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->withdrawn()->create();

        Livewire::test(ListDictionaryValues::class)
            ->assertCanSeeTableRecords([$position])
            ->assertCanNotSeeTableRecords([$colour])
            ->set('activeTab', 'colour')
            ->assertCanSeeTableRecords([$colour, $withdrawnColour])
            ->assertCanNotSeeTableRecords([$position])
            ->filterTable('withdrawn', true)
            ->assertCanSeeTableRecords([$withdrawnColour])
            ->assertCanNotSeeTableRecords([$colour]);
    }

    public function test_an_admin_creates_a_value_in_the_dictionary_of_the_active_tab(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());

        Livewire::test(ListDictionaryValues::class)
            ->set('activeTab', 'producer')
            ->callAction('create', ['dictionary' => 'producer', 'name' => 'Pilkington'])
            ->assertNotified();

        $this->assertSame(Dictionary::Producer, DictionaryValueModel::where('name', 'Pilkington')->firstOrFail()->dictionary);
    }

    public function test_the_create_form_preselects_the_active_tab(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());

        Livewire::test(ListDictionaryValues::class)
            ->set('activeTab', 'vehicle_body')
            ->mountAction('create')
            ->assertSchemaStateSet(['dictionary' => 'vehicle_body']);
    }

    public function test_a_duplicate_name_ignoring_case_is_refused_and_creates_nothing(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        DictionaryValueModelFactory::new()->of(Dictionary::Colour)->named('Zielony')->create();

        Livewire::test(ListDictionaryValues::class)
            ->callAction('create', ['dictionary' => 'colour', 'name' => 'ZIELONY'])
            ->assertNotified();

        $this->assertSame(1, DictionaryValueModel::count());
    }

    public function test_a_row_is_renamed_withdrawn_restored_and_deleted(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $value = DictionaryValueModelFactory::new()->named('Stara')->create();
        $page = Livewire::test(ListDictionaryValues::class);

        $page->callAction(TestAction::make('rename')->table($value), ['name' => 'Nowa']);
        $this->assertSame('Nowa', $value->fresh()->name);

        $page->callAction(TestAction::make('withdraw')->table($value));
        $this->assertTrue($value->fresh()->withdrawn);

        $page->callAction(TestAction::make('restore')->table($value));
        $this->assertFalse($value->fresh()->withdrawn);

        $page->callAction(TestAction::make('delete')->table($value));
        $this->assertNull(DictionaryValueModel::find($value->id));
    }
}
