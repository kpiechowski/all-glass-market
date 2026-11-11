<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\RemoveFitment;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\RemoveFitment\RemoveFitmentCommand;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** FR-10, §9 Glass model changed. */
class RemoveFitmentCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Actor
    {
        return Actor::user(UserModel::factory()->admin()->create()->id);
    }

    public function test_a_fitment_is_removed_and_the_model_is_announced(): void
    {
        Event::fake([GlassModelChanged::class]);
        [$a, $b] = [VehicleModelFactory::new()->create(), VehicleModelFactory::new()->create()];
        $model = GlassModelModelFactory::new()->fitting($a, $b)->create();

        app(CommandBus::class)->send(new RemoveFitmentCommand($this->admin(), $model->id, $a->id));

        $this->assertSame([$b->id], $model->fresh()->vehicles->pluck('id')->all());
        $this->assertNotNull(VehicleModel::find($a->id));
        Event::assertDispatched(GlassModelChanged::class, fn (GlassModelChanged $e): bool => $e->glassModelId === $model->id);
    }

    public function test_a_vehicle_the_model_does_not_fit_is_refused(): void
    {
        Event::fake([GlassModelChanged::class]);
        $model = GlassModelModelFactory::new()->create();
        $vehicle = VehicleModelFactory::new()->create();

        try {
            app(CommandBus::class)->send(new RemoveFitmentCommand($this->admin(), $model->id, $vehicle->id));
            $this->fail('A missing fitment was removed');
        } catch (GlassModelRefused) {
            Event::assertNotDispatched(GlassModelChanged::class);
        }
    }

    public function test_a_seller_is_refused(): void
    {
        $vehicle = VehicleModelFactory::new()->create();
        $model = GlassModelModelFactory::new()->fitting($vehicle)->create();

        $this->expectException(AuthorizationException::class);

        app(CommandBus::class)->send(new RemoveFitmentCommand(Actor::user(UserModel::factory()->create()->id), $model->id, $vehicle->id));
    }
}
