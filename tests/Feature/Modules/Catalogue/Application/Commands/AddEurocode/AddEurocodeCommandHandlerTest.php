<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\AddEurocode;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\AddEurocode\AddEurocodeCommand;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** WF-02 add, RULE-01, §9 Glass model changed. */
class AddEurocodeCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Actor
    {
        return Actor::user(UserModel::factory()->admin()->create()->id);
    }

    public function test_a_free_eurocode_is_normalised_added_as_non_main_and_announced(): void
    {
        Event::fake([GlassModelChanged::class]);
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();

        app(CommandBus::class)->send(new AddEurocodeCommand($this->admin(), $model->id, '8613 bgs'));

        $this->assertEqualsCanonicalizing(['8613AGS', '8613BGS'], $model->fresh()->eurocodes->pluck('code')->all());
        $this->assertSame('8613AGS', $model->fresh()->mainEurocode->code);
        Event::assertDispatched(GlassModelChanged::class, fn (GlassModelChanged $e): bool => $e->glassModelId === $model->id);
    }

    public function test_a_eurocode_already_on_the_model_changes_nothing_and_announces_nothing(): void
    {
        Event::fake([GlassModelChanged::class]);
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();

        app(CommandBus::class)->send(new AddEurocodeCommand($this->admin(), $model->id, '8613AGS'));

        $this->assertCount(1, $model->fresh()->eurocodes);
        Event::assertNotDispatched(GlassModelChanged::class);
    }

    public function test_a_eurocode_of_another_model_even_a_withdrawn_one_is_refused_naming_it(): void
    {
        Event::fake([GlassModelChanged::class]);
        $model = GlassModelModelFactory::new()->create();
        GlassModelModelFactory::new()->withdrawn()->withMainEurocode('8613XYZ')->create();

        try {
            app(CommandBus::class)->send(new AddEurocodeCommand($this->admin(), $model->id, '8613xyz'));
            $this->fail('A taken Eurocode was accepted');
        } catch (GlassModelRefused $e) {
            $this->assertSame('8613XYZ', $e->parameters['holder']);
            Event::assertNotDispatched(GlassModelChanged::class);
        }
    }

    public function test_a_blank_eurocode_is_refused(): void
    {
        $model = GlassModelModelFactory::new()->create();

        $this->expectException(GlassModelRefused::class);

        app(CommandBus::class)->send(new AddEurocodeCommand($this->admin(), $model->id, ' - '));
    }

    public function test_a_seller_is_rejected(): void
    {
        $model = GlassModelModelFactory::new()->create();

        $this->expectException(AuthorizationException::class);

        app(CommandBus::class)->send(new AddEurocodeCommand(Actor::user(UserModel::factory()->create()->id), $model->id, 'ABC1'));
    }
}
