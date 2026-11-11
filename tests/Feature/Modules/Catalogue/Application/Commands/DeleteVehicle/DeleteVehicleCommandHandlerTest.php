<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\DeleteVehicle;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\DeleteVehicle\DeleteVehicleCommand;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** FR-11, WF-05, §9 fan-out. */
class DeleteVehicleCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_deletes_a_vehicle(): void
    {
        $vehicle = VehicleModelFactory::new()->create();
        $other = VehicleModelFactory::new()->create();

        app(CommandBus::class)->send(new DeleteVehicleCommand(Actor::user(UserModel::factory()->admin()->create()->id), $vehicle->id));

        $this->assertNull(VehicleModel::find($vehicle->id));
        $this->assertNotNull(VehicleModel::find($other->id));
    }

    public function test_a_vehicle_that_fits_models_is_deleted_with_its_fitments_and_every_model_is_announced(): void
    {
        Event::fake([GlassModelChanged::class]);
        $vehicle = VehicleModelFactory::new()->create();
        $other = VehicleModelFactory::new()->create();
        $first = GlassModelModelFactory::new()->fitting($vehicle, $other)->create();
        $second = GlassModelModelFactory::new()->fitting($vehicle)->create();
        $unrelated = GlassModelModelFactory::new()->fitting($other)->create();

        app(CommandBus::class)->send(new DeleteVehicleCommand(Actor::user(UserModel::factory()->admin()->create()->id), $vehicle->id));

        $this->assertNull(VehicleModel::find($vehicle->id));
        $this->assertSame([$other->id], $first->fresh()->vehicles->pluck('id')->all());
        $this->assertCount(0, $second->fresh()->vehicles);
        $this->assertNotNull(GlassModelModel::find($second->id));
        Event::assertDispatchedTimes(GlassModelChanged::class, 2);
        Event::assertDispatched(GlassModelChanged::class, fn (GlassModelChanged $e): bool => $e->glassModelId === $first->id);
        Event::assertDispatched(GlassModelChanged::class, fn (GlassModelChanged $e): bool => $e->glassModelId === $second->id);
        Event::assertNotDispatched(GlassModelChanged::class, fn (GlassModelChanged $e): bool => $e->glassModelId === $unrelated->id);
    }

    public function test_a_vehicle_fitting_nothing_announces_nothing(): void
    {
        Event::fake([GlassModelChanged::class]);
        $vehicle = VehicleModelFactory::new()->create();

        app(CommandBus::class)->send(new DeleteVehicleCommand(Actor::user(UserModel::factory()->admin()->create()->id), $vehicle->id));

        Event::assertNotDispatched(GlassModelChanged::class);
    }

    public function test_a_seller_is_refused(): void
    {
        $vehicle = VehicleModelFactory::new()->create();

        $this->expectException(AuthorizationException::class);

        app(CommandBus::class)->send(new DeleteVehicleCommand(Actor::user(UserModel::factory()->create()->id), $vehicle->id));
    }
}
