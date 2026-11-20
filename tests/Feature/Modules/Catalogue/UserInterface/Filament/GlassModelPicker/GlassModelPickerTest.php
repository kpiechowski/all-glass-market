<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\GlassModelPicker;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelEurocodeModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\GlassModelPicker;
use App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\GlassModelPickerSlideOver;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\Fixtures\PickerHost;
use Tests\TestCase;

/** FR-19, FR-20, WF-06. */
class GlassModelPickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_a_seller_sees_active_models_only_and_no_withdrawn_toggle(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $active = GlassModelModelFactory::new()->withMainEurocode('1111AAA')->create();
        $withdrawn = GlassModelModelFactory::new()->withMainEurocode('2222BBB')->withdrawn()->create();

        $component = Livewire::test(GlassModelPicker::class)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$withdrawn])
            ->assertDontSee('ofert');

        $this->assertNull($component->instance()->getTable()->getFilter('with_withdrawn'));
    }

    public function test_a_seller_cannot_widen_the_picker_to_the_admin_variant(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $withdrawn = GlassModelModelFactory::new()->withMainEurocode('2222BBB')->withdrawn()->create();

        $component = Livewire::test(GlassModelPicker::class, ['variant' => 'admin'])
            ->assertCanNotSeeTableRecords([$withdrawn])
            ->assertDontSee('Utwórz nowy model szkła')
            ->call('mark', $withdrawn->id)
            ->assertSet('selectedId', null);

        $this->assertNull($component->instance()->getTable()->getFilter('with_withdrawn'));
    }

    public function test_the_admin_variant_lists_withdrawn_models_on_request(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $withdrawn = GlassModelModelFactory::new()->withMainEurocode('2222BBB')->withdrawn()->create();

        Livewire::test(GlassModelPicker::class)
            ->assertCanNotSeeTableRecords([$withdrawn])
            ->filterTable('with_withdrawn', true)
            ->assertCanSeeTableRecords([$withdrawn]);
    }

    public function test_clicking_a_card_marks_it_and_clicking_again_clears_it(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $first = GlassModelModelFactory::new()->withMainEurocode('1111AAA')->create();
        $second = GlassModelModelFactory::new()->withMainEurocode('3333CCC')->create();

        Livewire::test(GlassModelPicker::class)
            ->callTableColumnAction('card', $first)
            ->assertSet('selectedId', $first->id)
            ->callTableColumnAction('card', $second)
            ->assertSet('selectedId', $second->id)
            ->callTableColumnAction('card', $second)
            ->assertSet('selectedId', null);
    }

    public function test_it_opens_searching_for_the_prefill_text(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $hit = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        $other = GlassModelModelFactory::new()->withMainEurocode('1111AAA')->create();

        Livewire::test(GlassModelPicker::class, ['prefill' => '8613'])
            ->assertSet('tableSearch', '8613')
            ->assertCanSeeTableRecords([$hit])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_an_admin_creates_a_model_in_place_with_the_suggested_eurocode_and_it_is_marked(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->named('Przednia')->create();

        $component = Livewire::test(GlassModelPicker::class, ['prefill' => ' 8613 ags-mv '])
            ->mountTableAction('createGlassModelInPlace')
            ->assertTableActionDataSet(['main_eurocode' => '8613AGSMV'])
            ->setTableActionData(['position_id' => $position->id])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $created = GlassModelEurocodeModel::query()->where('code', '8613AGSMV')->firstOrFail();
        $this->assertTrue($created->is_main);
        $this->assertSame($position->id, GlassModelModel::query()->findOrFail($created->glass_model_id)->position_id);
        $component->assertSet('selectedId', $created->glass_model_id);
    }

    public function test_creating_with_a_taken_eurocode_is_refused_naming_the_holder(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $holder = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();

        Livewire::test(GlassModelPicker::class, ['prefill' => '8613 ags'])
            ->mountTableAction('createGlassModelInPlace')
            ->setTableActionData(['position_id' => $position->id])
            ->callMountedTableAction()
            ->assertHasTableActionErrors(['main_eurocode'])
            ->assertSet('selectedId', null);

        $this->assertSame(1, GlassModelEurocodeModel::query()->where('code', '8613AGS')->count());
        $this->assertSame($holder->id, GlassModelEurocodeModel::query()->where('code', '8613AGS')->value('glass_model_id'));
    }

    public function test_a_slide_over_action_hands_the_chosen_id_to_the_embedder(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $model = GlassModelModelFactory::new()->withMainEurocode('1111AAA')->create();

        Livewire::test(PickerHost::class)
            ->callAction('pick', [GlassModelPickerSlideOver::STATE_KEY => $model->id])
            ->assertSet('chosen', $model->id);
    }

    public function test_the_slide_over_holds_the_picker(): void
    {
        $this->actingAs(UserModel::factory()->create());

        Livewire::test(PickerHost::class)
            ->mountAction('pick')
            ->assertSeeLivewire(GlassModelPicker::class);
    }

    public function test_the_slide_over_cannot_be_confirmed_with_nothing_marked(): void
    {
        $this->actingAs(UserModel::factory()->create());

        Livewire::test(PickerHost::class)
            ->callAction('pick', [GlassModelPickerSlideOver::STATE_KEY => null])
            ->assertHasActionErrors([GlassModelPickerSlideOver::STATE_KEY => 'required'])
            ->assertSet('chosen', null);
    }
}
