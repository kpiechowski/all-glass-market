<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\RestoreGlassModel;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\RestoreGlassModel\RestoreGlassModelCommand;
use App\Modules\Catalogue\Domain\Events\GlassModelRestored;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** WF-04, FR-06, §9 Glass model Restored. */
class RestoreGlassModelCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_restores_a_model_and_it_is_announced(): void
    {
        Event::fake([GlassModelRestored::class]);
        $model = GlassModelModelFactory::new()->withdrawn()->create();

        app(CommandBus::class)->send(new RestoreGlassModelCommand(Actor::user(UserModel::factory()->admin()->create()->id), $model->id));

        $this->assertFalse($model->fresh()->withdrawn);
        Event::assertDispatched(GlassModelRestored::class, fn (GlassModelRestored $e): bool => $e->glassModelId === $model->id);
    }

    public function test_a_model_in_the_target_state_is_refused_and_announces_nothing(): void
    {
        Event::fake([GlassModelRestored::class]);
        $model = GlassModelModelFactory::new()->create();

        try {
            app(CommandBus::class)->send(new RestoreGlassModelCommand(Actor::user(UserModel::factory()->admin()->create()->id), $model->id));
            $this->fail('The refusal did not happen');
        } catch (GlassModelRefused) {
            Event::assertNotDispatched(GlassModelRestored::class);
        }
    }

    public function test_a_seller_is_rejected(): void
    {
        $model = GlassModelModelFactory::new()->withdrawn()->create();

        $this->expectException(AuthorizationException::class);

        app(CommandBus::class)->send(new RestoreGlassModelCommand(Actor::user(UserModel::factory()->create()->id), $model->id));
    }
}
