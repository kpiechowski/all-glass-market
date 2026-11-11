<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Pages;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Pages\ViewDictionaryValue;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Support\DictionaryValueViewSections;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewDictionaryValueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_a_seller_cannot_open_a_value(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $value = DictionaryValueModelFactory::new()->create();

        Livewire::test(ViewDictionaryValue::class, ['record' => $value->getKey()])->assertForbidden();
    }

    public function test_an_admin_sees_the_value_and_renames_it_from_the_page(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $value = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->named('Pilkington')->create();

        Livewire::test(ViewDictionaryValue::class, ['record' => $value->getKey()])
            ->assertSee('Pilkington')
            ->assertSee('Producent')
            ->callAction('rename', ['name' => 'Pilkington Group']);

        $this->assertSame('Pilkington Group', $value->fresh()->name);
    }

    public function test_deleting_from_the_page_removes_the_value(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $value = DictionaryValueModelFactory::new()->create();

        Livewire::test(ViewDictionaryValue::class, ['record' => $value->getKey()])
            ->callAction('delete')
            ->assertRedirect();

        $this->assertNull(DictionaryValueModel::find($value->id));
    }

    /** FR-25: renders nothing by itself, and shows the section another module registers. */
    public function test_a_section_registered_by_another_module_appears_on_the_view(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $value = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();

        Livewire::test(ViewDictionaryValue::class, ['record' => $value->getKey()])
            ->assertDontSee('Sekcja integracji');

        app(DictionaryValueViewSections::class)->register(
            fn (DictionaryValueModel $record): Section => Section::make('Sekcja integracji')
                ->schema([TextEntry::make('x')->state('id-'.$record->id)]),
        );

        Livewire::test(ViewDictionaryValue::class, ['record' => $value->getKey()])
            ->assertSee('Sekcja integracji')
            ->assertSee('id-'.$value->id);
    }
}
