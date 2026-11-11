<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\DeleteVehicle;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\DeleteVehicle\DeleteVehicleCommand;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-11 (fitment warning comes with ticket 05). */
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

    public function test_a_seller_is_refused(): void
    {
        $vehicle = VehicleModelFactory::new()->create();

        $this->expectException(AuthorizationException::class);

        app(CommandBus::class)->send(new DeleteVehicleCommand(Actor::user(UserModel::factory()->create()->id), $vehicle->id));
    }
}
