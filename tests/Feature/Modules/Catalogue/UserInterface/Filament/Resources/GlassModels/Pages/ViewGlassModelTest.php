<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages;

use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelOemCodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages\ViewGlassModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Support\GlassModelViewSections;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewGlassModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_a_seller_cannot_open_a_model(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $model = GlassModelModelFactory::new()->create();

        Livewire::test(ViewGlassModel::class, ['record' => $model->getKey()])->assertForbidden();
    }

    public function test_an_admin_sees_the_model(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        GlassModelOemCodeModelFactory::new()->code('1K0-845')->create(['glass_model_id' => $model->id]);

        Livewire::test(ViewGlassModel::class, ['record' => $model->getKey()])
            ->assertSee('8613AGS')
            ->assertSee($model->position->name)
            ->assertSee('1K0-845')
            ->assertSee('Aktywny');
    }

    public function test_withdraw_then_restore_from_the_page(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $model = GlassModelModelFactory::new()->create();

        Livewire::test(ViewGlassModel::class, ['record' => $model->getKey()])
            ->callAction('withdraw')
            ->callAction('restore');

        $this->assertFalse($model->fresh()->withdrawn);
    }

    public function test_deleting_from_the_page_removes_the_model(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $model = GlassModelModelFactory::new()->create();

        Livewire::test(ViewGlassModel::class, ['record' => $model->getKey()])
            ->callAction('delete')
            ->assertRedirect();

        $this->assertNull(GlassModelModel::find($model->id));
    }

    /** FR-25: renders nothing by itself, and shows the section another module registers. */
    public function test_a_section_registered_by_another_module_appears_on_the_view(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $model = GlassModelModelFactory::new()->create();

        Livewire::test(ViewGlassModel::class, ['record' => $model->getKey()])
            ->assertDontSee('Sekcja integracji');

        app(GlassModelViewSections::class)->register(
            fn (GlassModelModel $record): Section => Section::make('Sekcja integracji')
                ->schema([TextEntry::make('x')->state('id-'.$record->id)]),
        );

        Livewire::test(ViewGlassModel::class, ['record' => $model->getKey()])
            ->assertSee('Sekcja integracji')
            ->assertSee('id-'.$model->id);
    }
}
