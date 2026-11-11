<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\MoveEurocode;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\MoveEurocode\MoveEurocodeCommand;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelEurocodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** WF-02 move, RULE-01, RULE-02, §9 Glass model changed for both models. */
class MoveEurocodeCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Actor
    {
        return Actor::user(UserModel::factory()->admin()->create()->id);
    }

    public function test_a_eurocode_moves_to_another_model_as_non_main_and_both_are_announced(): void
    {
        Event::fake([GlassModelChanged::class]);
        $source = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        GlassModelEurocodeModelFactory::new()->create(['glass_model_id' => $source->id, 'code' => '8613BGS']);
        $target = GlassModelModelFactory::new()->withMainEurocode('9999TGT')->create();

        app(CommandBus::class)->send(new MoveEurocodeCommand($this->admin(), $source->id, '8613BGS', $target->id));

        $this->assertSame(['8613AGS'], $source->fresh()->eurocodes->pluck('code')->all());
        $this->assertEqualsCanonicalizing(['9999TGT', '8613BGS'], $target->fresh()->eurocodes->pluck('code')->all());
        $this->assertSame('9999TGT', $target->fresh()->mainEurocode->code);
        Event::assertDispatched(GlassModelChanged::class, fn (GlassModelChanged $e): bool => $e->glassModelId === $source->id);
        Event::assertDispatched(GlassModelChanged::class, fn (GlassModelChanged $e): bool => $e->glassModelId === $target->id);
    }

    public function test_a_withdrawn_model_may_receive_a_eurocode(): void
    {
        $source = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        GlassModelEurocodeModelFactory::new()->create(['glass_model_id' => $source->id, 'code' => '8613BGS']);
        $target = GlassModelModelFactory::new()->withdrawn()->create();

        app(CommandBus::class)->send(new MoveEurocodeCommand($this->admin(), $source->id, '8613BGS', $target->id));

        $this->assertContains('8613BGS', $target->fresh()->eurocodes->pluck('code')->all());
    }

    public function test_the_last_eurocode_is_refused_and_nothing_changes(): void
    {
        Event::fake([GlassModelChanged::class]);
        $source = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        $target = GlassModelModelFactory::new()->withMainEurocode('9999TGT')->create();

        try {
            app(CommandBus::class)->send(new MoveEurocodeCommand($this->admin(), $source->id, '8613AGS', $target->id));
            $this->fail('The last Eurocode moved');
        } catch (GlassModelRefused) {
            $this->assertCount(1, $source->fresh()->eurocodes);
            $this->assertCount(1, $target->fresh()->eurocodes);
            Event::assertNotDispatched(GlassModelChanged::class);
        }
    }

    public function test_the_main_eurocode_is_refused_while_others_exist(): void
    {
        $source = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        GlassModelEurocodeModelFactory::new()->create(['glass_model_id' => $source->id, 'code' => '8613BGS']);
        $target = GlassModelModelFactory::new()->create();

        try {
            app(CommandBus::class)->send(new MoveEurocodeCommand($this->admin(), $source->id, '8613AGS', $target->id));
            $this->fail('The main Eurocode moved');
        } catch (GlassModelRefused) {
            $this->assertCount(2, $source->fresh()->eurocodes);
            $this->assertNotContains('8613AGS', $target->fresh()->eurocodes->pluck('code')->all());
        }
    }

    public function test_moving_to_the_same_model_is_refused(): void
    {
        $source = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        GlassModelEurocodeModelFactory::new()->create(['glass_model_id' => $source->id, 'code' => '8613BGS']);

        $this->expectException(GlassModelRefused::class);

        app(CommandBus::class)->send(new MoveEurocodeCommand($this->admin(), $source->id, '8613BGS', $source->id));
    }

    public function test_a_seller_is_rejected(): void
    {
        $source = GlassModelModelFactory::new()->create();
        $target = GlassModelModelFactory::new()->create();

        $this->expectException(AuthorizationException::class);

        app(CommandBus::class)->send(new MoveEurocodeCommand(Actor::user(UserModel::factory()->create()->id), $source->id, 'ABC1', $target->id));
    }
}
