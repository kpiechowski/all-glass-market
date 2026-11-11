<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\UpdateGlassModel;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\UpdateGlassModel\UpdateGlassModelCommand;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelOemCodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** WF-03, FR-02, FR-24 (a withdrawn value stays), §9 Glass model changed. */
class UpdateGlassModelCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Actor
    {
        return Actor::user(UserModel::factory()->admin()->create()->id);
    }

    /** @param  array<string, mixed>  $overrides */
    private function send(Actor $actor, GlassModelModel $model, array $overrides = []): void
    {
        app(CommandBus::class)->send(new UpdateGlassModelCommand(...[
            'actor' => $actor,
            'glassModelId' => $model->id,
            'positionId' => $model->position_id,
            'colourId' => $model->colour_id,
            'equipmentIds' => $model->equipment->modelKeys(),
            'oemCodes' => $model->oemCodes->pluck('code')->all(),
            ...$overrides,
        ]));
    }

    public function test_an_admin_changes_the_parameters_and_the_model_is_announced(): void
    {
        Event::fake([GlassModelChanged::class]);
        $model = GlassModelModelFactory::new()->create();
        GlassModelOemCodeModelFactory::new()->code('OLD-1')->create(['glass_model_id' => $model->id]);
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create();

        $this->send($this->admin(), $model->load('equipment', 'oemCodes'), [
            'positionId' => $position->id,
            'colourId' => $colour->id,
            'equipmentIds' => [$equipment->id],
            'oemCodes' => ['new 2'],
        ]);

        $fresh = GlassModelModel::query()->with(['oemCodes', 'equipment'])->findOrFail($model->id);
        $this->assertSame($position->id, $fresh->position_id);
        $this->assertSame($colour->id, $fresh->colour_id);
        $this->assertSame([$equipment->id], $fresh->equipment->modelKeys());
        $this->assertSame(['NEW 2'], $fresh->oemCodes->pluck('code')->all());
        Event::assertDispatched(GlassModelChanged::class, fn (GlassModelChanged $e): bool => $e->glassModelId === $model->id);
    }

    public function test_saving_without_a_change_announces_nothing(): void
    {
        Event::fake([GlassModelChanged::class]);
        $model = GlassModelModelFactory::new()->create();

        $this->send($this->admin(), $model->load('equipment', 'oemCodes'));

        Event::assertNotDispatched(GlassModelChanged::class);
    }

    public function test_a_withdrawn_model_can_be_edited(): void
    {
        $model = GlassModelModelFactory::new()->withdrawn()->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();

        $this->send($this->admin(), $model->load('equipment', 'oemCodes'), ['colourId' => $colour->id]);

        $this->assertSame($colour->id, $model->fresh()->colour_id);
        $this->assertTrue($model->fresh()->withdrawn);
    }

    public function test_a_withdrawn_value_already_on_the_model_survives_a_save(): void
    {
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->withdrawn()->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->withdrawn()->create();
        $model = GlassModelModelFactory::new()->withColour($colour)->withEquipment($equipment)->create();

        $this->send($this->admin(), $model->load('equipment', 'oemCodes'), ['oemCodes' => ['A1']]);

        $fresh = $model->fresh()->load('equipment');
        $this->assertSame($colour->id, $fresh->colour_id);
        $this->assertSame([$equipment->id], $fresh->equipment->modelKeys());
    }

    public function test_a_withdrawn_value_cannot_be_newly_chosen(): void
    {
        $model = GlassModelModelFactory::new()->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->withdrawn()->create();

        $this->expectException(GlassModelRefused::class);

        $this->send($this->admin(), $model->load('equipment', 'oemCodes'), ['colourId' => $colour->id]);
    }

    public function test_a_missing_position_is_refused_and_nothing_is_announced(): void
    {
        Event::fake([GlassModelChanged::class]);
        $model = GlassModelModelFactory::new()->create();

        try {
            $this->send($this->admin(), $model->load('equipment', 'oemCodes'), ['positionId' => null]);
            $this->fail('A missing position was accepted');
        } catch (GlassModelRefused) {
            Event::assertNotDispatched(GlassModelChanged::class);
        }
    }

    public function test_a_seller_is_rejected(): void
    {
        $model = GlassModelModelFactory::new()->create();

        $this->expectException(AuthorizationException::class);

        $this->send(Actor::user(UserModel::factory()->create()->id), $model->load('equipment', 'oemCodes'));
    }
}
