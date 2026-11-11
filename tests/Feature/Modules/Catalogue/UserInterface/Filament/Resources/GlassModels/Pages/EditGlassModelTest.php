<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages\EditGlassModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditGlassModelTest extends TestCase
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
        $model = GlassModelModelFactory::new()->create();

        Livewire::test(EditGlassModel::class, ['record' => $model->getKey()])->assertForbidden();
    }

    public function test_the_form_is_filled_and_a_withdrawn_model_is_saved_through_the_command(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->withdrawn()->create();
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->withColour($colour)->withdrawn()->create();
        $newColour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();

        Livewire::test(EditGlassModel::class, ['record' => $model->getKey()])
            ->assertFormSet(['main_eurocode' => '8613AGS', 'colour_id' => $colour->id])
            ->fillForm(['colour_id' => $newColour->id, 'oem_codes' => ['A-1']])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $model->fresh()->load('oemCodes');
        $this->assertSame($newColour->id, $fresh->colour_id);
        $this->assertSame(['A-1'], $fresh->oemCodes->pluck('code')->all());
        $this->assertTrue($fresh->withdrawn);
    }

    public function test_a_withdrawn_colour_already_on_the_model_is_saved_unchanged(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->withdrawn()->create();
        $model = GlassModelModelFactory::new()->withColour($colour)->create();

        Livewire::test(EditGlassModel::class, ['record' => $model->getKey()])
            ->fillForm(['oem_codes' => ['B-2']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($colour->id, $model->fresh()->colour_id);
    }
}
