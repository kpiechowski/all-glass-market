<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages;

use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
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
}
