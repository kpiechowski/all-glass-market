<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence;

use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Catalogue\Domain\Entities\GlassModel;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use App\Modules\Catalogue\Domain\ValueObjects\OemCode;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelEurocodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentGlassModelRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): GlassModelRepository
    {
        return app(GlassModelRepository::class);
    }

    public function test_an_added_model_is_found_with_all_its_parts(): void
    {
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create();

        $id = app(ActorStack::class)->run(Actor::system('test'), fn (): int => $this->repository()->add(
            GlassModel::create(Eurocode::from('8613AGS'), $position->id, null, [$equipment->id], [OemCode::tryFrom('1K0-845')], null),
        ));

        $entity = $this->repository()->get($id);
        $this->assertSame('8613AGS', $entity->mainEurocode()->value);
        $this->assertSame([$equipment->id], $entity->equipmentIds());
        $this->assertSame('1K0-845', $entity->oemCodes()[0]->value);
    }

    public function test_a_saved_fitment_is_stored_and_the_ids_fitting_a_vehicle_exclude_other_models(): void
    {
        [$vehicle, $other] = [VehicleModelFactory::new()->create(), VehicleModelFactory::new()->create()];
        $fitting = GlassModelModelFactory::new()->create();
        $elsewhere = GlassModelModelFactory::new()->fitting($other)->create();

        app(ActorStack::class)->run(Actor::system('test'), function () use ($fitting, $vehicle): void {
            $entity = $this->repository()->get($fitting->id);
            $entity->fitVehicles([$vehicle->id]);
            $this->repository()->save($entity);
        });

        $this->assertSame([$vehicle->id], $this->repository()->get($fitting->id)->vehicleIds());
        $this->assertSame([$fitting->id], $this->repository()->idsFittingVehicle($vehicle->id));
        $this->assertSame([$elsewhere->id], $this->repository()->idsFittingVehicle($other->id));
    }

    public function test_a_saved_change_replaces_the_parts_and_touches_the_model(): void
    {
        $model = GlassModelModelFactory::new()->create();
        ReadOnlyModel::writing(fn () => $model->forceFill(['updated_at' => now()->subDay()])->saveQuietly());
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create();

        app(ActorStack::class)->run(Actor::system('test'), function () use ($model, $equipment): void {
            $entity = $this->repository()->get($model->id);
            $entity->changeParameters($entity->positionId(), null, [$equipment->id], [OemCode::tryFrom('A-1')]);
            $this->repository()->save($entity);
        });

        $reloaded = $this->repository()->get($model->id);
        $this->assertSame([$equipment->id], $reloaded->equipmentIds());
        $this->assertSame(['A-1'], array_map(fn (OemCode $c): string => $c->value, $reloaded->oemCodes()));
        $this->assertTrue($model->fresh()->updated_at->isToday());
    }

    public function test_the_holder_of_a_eurocode_is_found_withdrawn_models_included_and_the_excepted_model_left_out(): void
    {
        $active = GlassModelModelFactory::new()->withMainEurocode('AAA1')->create();
        $withdrawn = GlassModelModelFactory::new()->withMainEurocode('BBB2')->withdrawn()->create();
        GlassModelEurocodeModelFactory::new()->create(['glass_model_id' => $withdrawn->id, 'code' => 'BBB3']);

        $this->assertSame($active->id, $this->repository()->holderOfEurocode(Eurocode::from('aaa1'))->glassModelId);
        $holder = $this->repository()->holderOfEurocode(Eurocode::from('BBB3'));
        $this->assertSame($withdrawn->id, $holder->glassModelId);
        $this->assertSame('BBB2', $holder->mainEurocode);
        $this->assertNull($this->repository()->holderOfEurocode(Eurocode::from('AAA1'), $active->id));
        $this->assertNull($this->repository()->holderOfEurocode(Eurocode::from('CCC4')));
    }

    public function test_the_models_using_a_value_are_listed_and_others_excluded(): void
    {
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create();
        $withColour = GlassModelModelFactory::new()->withColour($colour)->create();
        $withEquipment = GlassModelModelFactory::new()->withEquipment($equipment)->create();
        $plain = GlassModelModelFactory::new()->create();

        $this->assertSame([$withColour->id], $this->repository()->idsUsing(Dictionary::Colour, $colour->id));
        $this->assertSame([$withEquipment->id], $this->repository()->idsUsing(Dictionary::Equipment, $equipment->id));
        $this->assertSame([$plain->id], $this->repository()->idsUsing(Dictionary::Position, $plain->position_id));
        $this->assertSame([], $this->repository()->idsUsing(Dictionary::Producer, $colour->id));
    }
}
