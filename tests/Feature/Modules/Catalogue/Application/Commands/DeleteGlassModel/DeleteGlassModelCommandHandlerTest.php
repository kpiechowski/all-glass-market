<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\DeleteGlassModel;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\DeleteGlassModel\DeleteGlassModelCommand;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelUsages;
use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;
use App\Modules\Catalogue\Domain\Contracts\GlassModelUsage;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelEurocodeModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** WF-04, FR-24. */
class DeleteGlassModelCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(UserModel $user, int $id): void
    {
        app(CommandBus::class)->send(new DeleteGlassModelCommand(Actor::user($user->id), $id));
    }

    public function test_an_unused_model_is_deleted_with_its_parts(): void
    {
        $model = GlassModelModelFactory::new()->create();

        $this->send(UserModel::factory()->admin()->create(), $model->id);

        $this->assertNull(GlassModelModel::find($model->id));
        $this->assertSame(0, GlassModelEurocodeModel::query()->count());
    }

    public function test_a_model_in_use_is_refused_naming_where(): void
    {
        $model = GlassModelModelFactory::new()->create();
        app(GlassModelUsages::class)->register(new class implements GlassModelUsage
        {
            public function usesOf(int $glassModelId): array
            {
                return [new DictionaryUse('catalogue::uses.glass_models', 3)];
            }
        });

        try {
            $this->send(UserModel::factory()->admin()->create(), $model->id);
            $this->fail('A model in use was deleted');
        } catch (GlassModelRefused $e) {
            $this->assertStringContainsString('(3)', $e->parameters['places']);
            $this->assertNotNull(GlassModelModel::find($model->id));
        }
    }

    public function test_a_seller_is_rejected(): void
    {
        $model = GlassModelModelFactory::new()->create();

        $this->expectException(AuthorizationException::class);

        $this->send(UserModel::factory()->create(), $model->id);
    }
}
