<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\RelationManagers;

use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages\ViewGlassModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\RelationManagers\FittedVehiclesRelationManager;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-10: the fitment tab of a Glass model. */
class FittedVehiclesRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    /** @return Testable */
    private function tab($model)
    {
        return Livewire::test(FittedVehiclesRelationManager::class, ['ownerRecord' => $model, 'pageClass' => ViewGlassModel::class]);
    }

    public function test_it_lists_only_the_vehicles_the_model_fits(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $fitted = VehicleModelFactory::new()->create();
        $other = VehicleModelFactory::new()->create();
        $model = GlassModelModelFactory::new()->fitting($fitted)->create();

        $this->tab($model)
            ->assertCanSeeTableRecords([$fitted])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_an_admin_fits_vehicles_through_the_command(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        [$a, $b] = [VehicleModelFactory::new()->create(), VehicleModelFactory::new()->create()];
        $model = GlassModelModelFactory::new()->create();

        $this->tab($model)
            ->callAction(TestAction::make('fitVehicles')->table(), ['vehicle_ids' => [$a->id, $b->id]])
            ->assertNotified();

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $model->fresh()->vehicles->pluck('id')->all());
    }

    public function test_an_admin_removes_a_fitment_and_the_vehicle_stays(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        [$a, $b] = [VehicleModelFactory::new()->create(), VehicleModelFactory::new()->create()];
        $model = GlassModelModelFactory::new()->fitting($a, $b)->create();

        $this->tab($model)
            ->callAction(TestAction::make('removeFitment')->table($a))
            ->assertNotified();

        $this->assertSame([$b->id], $model->fresh()->vehicles->pluck('id')->all());
        $this->assertNotNull($a->fresh());
    }
}
