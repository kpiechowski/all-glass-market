<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\Pages\CatalogueSettings;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\ThumbnailMappingModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\ThumbnailMappingModel;
use App\Modules\Catalogue\UserInterface\Filament\Pages\CatalogueSettings\CatalogueSettings;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-15-FR-17: the settings page writes through the command. */
class CatalogueSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_a_seller_cannot_open_the_page(): void
    {
        $this->actingAs(UserModel::factory()->create());

        Livewire::test(CatalogueSettings::class)->assertForbidden();
    }

    public function test_the_page_shows_the_stored_mappings(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->named('Lewa przednia')->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->named('Zielony')->create();
        ThumbnailMappingModelFactory::new()->forPosition($position, GlassShape::SideDrop, true)->create();
        ThumbnailMappingModelFactory::new()->forColour($colour, '#1a8a3c', 40)->create();

        Livewire::test(CatalogueSettings::class)
            ->assertSee('Lewa przednia')
            ->assertFormSet([
                "positions.{$position->id}.shape" => 'side_drop',
                "positions.{$position->id}.mirrored" => true,
                "colours.{$colour->id}.custom" => true,
                "colours.{$colour->id}.opacity" => 40,
            ]);
    }

    public function test_an_admin_saves_the_mappings_through_the_command(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create();

        Livewire::test(CatalogueSettings::class)
            ->fillForm([
                "positions.{$position->id}.shape" => 'roof',
                "positions.{$position->id}.mirrored" => true,
                "colours.{$colour->id}.custom" => true,
                "colours.{$colour->id}.fill" => '#336699',
                "colours.{$colour->id}.opacity" => 70,
                "equipment.{$equipment->id}.pictogram" => 'heated',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(GlassShape::Roof, ThumbnailMappingModel::query()->where('dictionary_value_id', $position->id)->sole()->shape);
        $this->assertSame('#336699', ThumbnailMappingModel::query()->where('dictionary_value_id', $colour->id)->sole()->fill);
        $this->assertSame(Pictogram::Heated, ThumbnailMappingModel::query()->where('dictionary_value_id', $equipment->id)->sole()->pictogram);
    }

    public function test_switching_a_colour_back_to_no_fill_removes_its_mapping(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        ThumbnailMappingModelFactory::new()->forColour($colour, '#1a8a3c', 40)->create();

        Livewire::test(CatalogueSettings::class)
            ->fillForm(["colours.{$colour->id}.custom" => false])
            ->call('save');

        $this->assertSame(0, ThumbnailMappingModel::query()->count());
    }
}
