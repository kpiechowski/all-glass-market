<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\CreateGlassModel;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\CreateGlassModel\CreateGlassModelCommand;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** WF-01, FR-01, RULE-01, RULE-03, RULE-04, RULE-05. */
class CreateGlassModelCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Actor
    {
        return Actor::user(UserModel::factory()->admin()->create()->id);
    }

    /** @param  array<string, mixed>  $overrides */
    private function send(Actor $actor, array $overrides = []): int
    {
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();

        return app(CommandBus::class)->send(new CreateGlassModelCommand(...[
            'actor' => $actor,
            'mainEurocode' => '8613 agsmv',
            'positionId' => $position->id,
            ...$overrides,
        ]));
    }

    public function test_an_admin_creates_a_model_with_its_parameters(): void
    {
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create();

        $id = $this->send($this->admin(), [
            'colourId' => $colour->id,
            'equipmentIds' => [$equipment->id],
            'oemCodes' => ['1k0-845 011', '1K0 845-011', ' 2k0 '],
        ]);

        $model = GlassModelModel::query()->with(['eurocodes', 'oemCodes', 'equipment'])->findOrFail($id);
        $this->assertSame(['8613AGSMV'], $model->eurocodes->pluck('code')->all());
        $this->assertTrue($model->eurocodes->first()->is_main);
        $this->assertSame($colour->id, $model->colour_id);
        $this->assertSame([$equipment->id], $model->equipment->modelKeys());
        $this->assertSame(['1K0-845 011', '2K0'], $model->oemCodes->pluck('code')->all());
        $this->assertFalse($model->withdrawn);
    }

    public function test_creation_announces_nothing(): void
    {
        Event::fake([GlassModelChanged::class]);

        $this->send($this->admin());

        Event::assertNotDispatched(GlassModelChanged::class);
    }

    public function test_a_taken_eurocode_is_refused_naming_the_model_holding_it(): void
    {
        $holder = GlassModelModelFactory::new()->withMainEurocode('8613AGSMV')->create();

        try {
            $this->send($this->admin());
            $this->fail('A taken Eurocode was accepted');
        } catch (GlassModelRefused $e) {
            $this->assertSame('8613AGSMV', $e->parameters['holder']);
            $this->assertSame(1, GlassModelModel::query()->count());
            $this->assertSame($holder->id, GlassModelModel::query()->value('id'));
        }
    }

    public function test_a_eurocode_held_by_a_withdrawn_model_is_still_taken(): void
    {
        GlassModelModelFactory::new()->withMainEurocode('8613AGSMV')->withdrawn()->create();

        $this->expectException(GlassModelRefused::class);

        $this->send($this->admin());
    }

    public function test_an_empty_eurocode_is_refused(): void
    {
        $this->expectException(GlassModelRefused::class);

        $this->send($this->admin(), ['mainEurocode' => ' - ']);
    }

    public function test_an_over_long_eurocode_is_refused(): void
    {
        $this->expectException(GlassModelRefused::class);

        $this->send($this->admin(), ['mainEurocode' => str_repeat('A', 18)]);
    }

    public function test_a_missing_position_is_refused(): void
    {
        $this->expectException(GlassModelRefused::class);

        $this->send($this->admin(), ['positionId' => null]);
    }

    public function test_a_withdrawn_colour_cannot_be_chosen(): void
    {
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->withdrawn()->create();

        $this->expectException(GlassModelRefused::class);

        $this->send($this->admin(), ['colourId' => $colour->id]);
    }

    public function test_a_value_of_another_dictionary_is_refused(): void
    {
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();

        $this->expectException(GlassModelRefused::class);

        $this->send($this->admin(), ['colourId' => $producer->id]);
    }

    public function test_a_seller_is_rejected(): void
    {
        $this->expectException(AuthorizationException::class);

        $this->send(Actor::user(UserModel::factory()->create()->id));
    }
}
