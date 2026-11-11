<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\ChangeMainEurocode;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\ChangeMainEurocode\ChangeMainEurocodeCommand;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelEurocodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** WF-02 change main, RULE-02, §9 Glass model changed. */
class ChangeMainEurocodeCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Actor
    {
        return Actor::user(UserModel::factory()->admin()->create()->id);
    }

    public function test_another_eurocode_becomes_main_and_the_model_is_announced(): void
    {
        Event::fake([GlassModelChanged::class]);
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        GlassModelEurocodeModelFactory::new()->create(['glass_model_id' => $model->id, 'code' => '8613BGS']);

        app(CommandBus::class)->send(new ChangeMainEurocodeCommand($this->admin(), $model->id, '8613BGS'));

        $this->assertSame('8613BGS', $model->fresh()->mainEurocode->code);
        $this->assertSame(1, $model->fresh()->eurocodes->where('is_main', true)->count());
        Event::assertDispatched(GlassModelChanged::class, fn (GlassModelChanged $e): bool => $e->glassModelId === $model->id);
    }

    public function test_the_current_main_changes_nothing_and_announces_nothing(): void
    {
        Event::fake([GlassModelChanged::class]);
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();

        app(CommandBus::class)->send(new ChangeMainEurocodeCommand($this->admin(), $model->id, '8613AGS'));

        Event::assertNotDispatched(GlassModelChanged::class);
    }

    public function test_a_eurocode_the_model_does_not_hold_is_refused(): void
    {
        $model = GlassModelModelFactory::new()->create();

        $this->expectException(GlassModelRefused::class);

        app(CommandBus::class)->send(new ChangeMainEurocodeCommand($this->admin(), $model->id, 'NOPE1'));
    }

    public function test_a_seller_is_rejected(): void
    {
        $model = GlassModelModelFactory::new()->create();

        $this->expectException(AuthorizationException::class);

        app(CommandBus::class)->send(new ChangeMainEurocodeCommand(Actor::user(UserModel::factory()->create()->id), $model->id, 'ABC1'));
    }
}
