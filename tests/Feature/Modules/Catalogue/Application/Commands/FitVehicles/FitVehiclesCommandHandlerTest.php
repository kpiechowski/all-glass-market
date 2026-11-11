<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\FitVehicles;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\FitVehicles\FitVehiclesCommand;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** FR-10, §9 Glass model changed. */
class FitVehiclesCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Actor
    {
        return Actor::user(UserModel::factory()->admin()->create()->id);
    }

    public function test_vehicles_are_fitted_once_and_the_model_is_announced(): void
    {
        Event::fake([GlassModelChanged::class]);
        $fitted = VehicleModelFactory::new()->create();
        [$a, $b] = [VehicleModelFactory::new()->create(), VehicleModelFactory::new()->create()];
        $model = GlassModelModelFactory::new()->fitting($fitted)->create();

        app(CommandBus::class)->send(new FitVehiclesCommand($this->admin(), $model->id, [$fitted->id, $a->id, $b->id, $a->id]));

        $this->assertEqualsCanonicalizing([$fitted->id, $a->id, $b->id], $model->fresh()->vehicles->pluck('id')->all());
        Event::assertDispatchedTimes(GlassModelChanged::class, 1);
        Event::assertDispatched(GlassModelChanged::class, fn (GlassModelChanged $e): bool => $e->glassModelId === $model->id);
    }

    public function test_fitting_only_what_is_already_fitted_announces_nothing(): void
    {
        Event::fake([GlassModelChanged::class]);
        $vehicle = VehicleModelFactory::new()->create();
        $model = GlassModelModelFactory::new()->fitting($vehicle)->create();

        app(CommandBus::class)->send(new FitVehiclesCommand($this->admin(), $model->id, [$vehicle->id]));

        Event::assertNotDispatched(GlassModelChanged::class);
    }

    public function test_a_withdrawn_model_can_be_fitted(): void
    {
        $vehicle = VehicleModelFactory::new()->create();
        $model = GlassModelModelFactory::new()->withdrawn()->create();

        app(CommandBus::class)->send(new FitVehiclesCommand($this->admin(), $model->id, [$vehicle->id]));

        $this->assertSame([$vehicle->id], $model->fresh()->vehicles->pluck('id')->all());
    }

    public function test_an_unknown_vehicle_is_refused_and_nothing_is_fitted(): void
    {
        $vehicle = VehicleModelFactory::new()->create();
        $model = GlassModelModelFactory::new()->create();

        try {
            app(CommandBus::class)->send(new FitVehiclesCommand($this->admin(), $model->id, [$vehicle->id, 99999]));
            $this->fail('An unknown vehicle was accepted');
        } catch (ModelNotFoundException) {
            $this->assertCount(0, $model->fresh()->vehicles);
        }
    }

    public function test_a_seller_is_refused(): void
    {
        $model = GlassModelModelFactory::new()->create();

        $this->expectException(AuthorizationException::class);

        app(CommandBus::class)->send(new FitVehiclesCommand(Actor::user(UserModel::factory()->create()->id), $model->id, []));
    }
}
