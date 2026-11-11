<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages\CreateGlassModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateGlassModelTest extends TestCase
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

        Livewire::test(CreateGlassModel::class)->assertForbidden();
    }

    public function test_an_admin_creates_a_model_through_the_command(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create();

        Livewire::test(CreateGlassModel::class)
            ->fillForm([
                'main_eurocode' => '8613 agsmv',
                'position_id' => $position->id,
                'colour_id' => $colour->id,
                'equipment_ids' => [$equipment->id],
                'oem_codes' => ['1k0-845'],
                'further_eurocodes' => ['8613 bgsmv'],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $model = GlassModelModel::query()->with(['mainEurocode', 'eurocodes', 'oemCodes', 'equipment'])->firstOrFail();
        $this->assertSame('8613AGSMV', $model->mainEurocode->code);
        $this->assertEqualsCanonicalizing(['8613AGSMV', '8613BGSMV'], $model->eurocodes->pluck('code')->all());
        $this->assertSame($colour->id, $model->colour_id);
        $this->assertSame([$equipment->id], $model->equipment->modelKeys());
        $this->assertSame(['1K0-845'], $model->oemCodes->pluck('code')->all());
    }

    public function test_the_position_is_required(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());

        Livewire::test(CreateGlassModel::class)
            ->fillForm(['main_eurocode' => '8613AGS', 'position_id' => null])
            ->call('create')
            ->assertHasFormErrors(['position_id' => 'required']);

        $this->assertSame(0, GlassModelModel::query()->count());
    }

    public function test_a_taken_eurocode_keeps_the_form_open_and_names_the_holder(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();

        Livewire::test(CreateGlassModel::class)
            ->fillForm(['main_eurocode' => '8613-ags', 'position_id' => $position->id])
            ->call('create')
            ->assertNotified()
            ->assertNoRedirect();

        $this->assertSame(1, GlassModelModel::query()->count());
    }

    public function test_the_position_options_leave_out_withdrawn_values(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $active = DictionaryValueModelFactory::new()->of(Dictionary::Position)->named('Przednia')->create();
        $withdrawn = DictionaryValueModelFactory::new()->of(Dictionary::Position)->named('Wycofana szyba')->withdrawn()->create();

        Livewire::test(CreateGlassModel::class)
            ->assertSee('Przednia')
            ->assertFormFieldExists('position_id', fn ($field): bool => array_key_exists($active->id, $field->getOptions())
                && ! array_key_exists($withdrawn->id, $field->getOptions()));
    }
}
