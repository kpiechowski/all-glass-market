<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\RemoveEurocode;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\RemoveEurocode\RemoveEurocodeCommand;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelEurocodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** WF-02 remove, RULE-02, §9 Glass model changed. */
class RemoveEurocodeCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Actor
    {
        return Actor::user(UserModel::factory()->admin()->create()->id);
    }

    public function test_a_non_main_eurocode_leaves_the_model_and_the_catalogue(): void
    {
        Event::fake([GlassModelChanged::class]);
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        GlassModelEurocodeModelFactory::new()->create(['glass_model_id' => $model->id, 'code' => '8613BGS']);

        app(CommandBus::class)->send(new RemoveEurocodeCommand($this->admin(), $model->id, '8613BGS'));

        $this->assertSame(['8613AGS'], $model->fresh()->eurocodes->pluck('code')->all());
        $this->assertDatabaseMissing('catalogue_glass_model_eurocodes', ['code' => '8613BGS']);
        Event::assertDispatched(GlassModelChanged::class, fn (GlassModelChanged $e): bool => $e->glassModelId === $model->id);
    }

    public function test_the_last_eurocode_is_refused(): void
    {
        Event::fake([GlassModelChanged::class]);
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();

        try {
            app(CommandBus::class)->send(new RemoveEurocodeCommand($this->admin(), $model->id, '8613AGS'));
            $this->fail('The last Eurocode was removed');
        } catch (GlassModelRefused) {
            $this->assertCount(1, $model->fresh()->eurocodes);
            Event::assertNotDispatched(GlassModelChanged::class);
        }
    }

    public function test_the_main_eurocode_is_refused_while_others_exist(): void
    {
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        GlassModelEurocodeModelFactory::new()->create(['glass_model_id' => $model->id, 'code' => '8613BGS']);

        try {
            app(CommandBus::class)->send(new RemoveEurocodeCommand($this->admin(), $model->id, '8613AGS'));
            $this->fail('The main Eurocode was removed');
        } catch (GlassModelRefused) {
            $this->assertCount(2, $model->fresh()->eurocodes);
        }
    }

    public function test_a_seller_is_rejected(): void
    {
        $model = GlassModelModelFactory::new()->create();

        $this->expectException(AuthorizationException::class);

        app(CommandBus::class)->send(new RemoveEurocodeCommand(Actor::user(UserModel::factory()->create()->id), $model->id, 'ABC1'));
    }
}
