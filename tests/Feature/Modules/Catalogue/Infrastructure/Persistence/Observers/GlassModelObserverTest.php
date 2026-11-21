<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence\Observers;

use App\Core\Domain\Actor;
use App\Core\Domain\Events\ChangeKind;
use App\Core\Domain\Events\RecordChanged;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Modules\Catalogue\Domain\Entities\GlassModel;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use App\Modules\Catalogue\Domain\ValueObjects\OemCode;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** Every change of a Glass model, parts included, reaches the history as one RecordChanged of the model (Catalogue 13). */
class GlassModelObserverTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): GlassModelRepository
    {
        return app(GlassModelRepository::class);
    }

    /** Runs `$change` on the loaded entity as one command and saves it. */
    private function change(int $id, Closure $change): void
    {
        app(ActorStack::class)->run(Actor::system('test'), function () use ($id, $change): void {
            $entity = $this->repository()->get($id);
            $change($entity);
            $this->repository()->save($entity);
        });
    }

    /** @return list<RecordChanged> */
    private function recorded(): array
    {
        return Event::dispatched(RecordChanged::class)->map(fn (array $args): RecordChanged => $args[0])
            ->filter(fn (RecordChanged $event): bool => $event->modelClass === GlassModelModel::class)->values()->all();
    }

    public function test_adding_a_model_records_created_with_its_parts_as_added_relation_items(): void
    {
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create();
        $vehicle = VehicleModelFactory::new()->create();
        Event::fake([RecordChanged::class]);

        $id = app(ActorStack::class)->run(Actor::system('test'), function () use ($position, $equipment, $vehicle): int {
            $id = $this->repository()->add(GlassModel::create(Eurocode::from('8613AGS'), $position->id, null, [$equipment->id], [OemCode::tryFrom('1K0-845')], null));
            $entity = $this->repository()->get($id);
            $entity->fitVehicles([$vehicle->id]);
            $this->repository()->save($entity);

            return $id;
        });

        $events = $this->recorded();
        $this->assertCount(1, $events);
        $this->assertSame(ChangeKind::Created, $events[0]->kind);
        $this->assertSame($id, $events[0]->id);
        $this->assertSame(['added' => ['8613AGS'], 'removed' => []], $events[0]->relationChanges['eurocodes']);
        $this->assertSame(['added' => ['1K0-845'], 'removed' => []], $events[0]->relationChanges['oem_codes']);
        $this->assertSame(['added' => [$equipment->id], 'removed' => []], $events[0]->relationChanges['equipment']);
        $this->assertSame(['added' => [$vehicle->id], 'removed' => []], $events[0]->relationChanges['vehicles']);
    }

    public function test_changing_a_field_records_changed_with_old_and_new_value(): void
    {
        $model = GlassModelModelFactory::new()->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        Event::fake([RecordChanged::class]);

        $this->change($model->id, fn (GlassModel $entity) => $entity->changeParameters($entity->positionId(), $colour->id, [], []));

        $events = $this->recorded();
        $this->assertCount(1, $events);
        $this->assertSame(ChangeKind::Changed, $events[0]->kind);
        $this->assertSame(['old' => null, 'new' => $colour->id], $events[0]->changes['colour_id']);
    }

    public function test_withdrawing_and_restoring_record_the_withdrawn_field_as_changed(): void
    {
        $model = GlassModelModelFactory::new()->create();
        Event::fake([RecordChanged::class]);

        $this->change($model->id, fn (GlassModel $entity) => $entity->withdraw());
        $this->change($model->id, fn (GlassModel $entity) => $entity->restore());

        $events = $this->recorded();
        $this->assertCount(2, $events);
        $this->assertSame(ChangeKind::Changed, $events[0]->kind);
        $this->assertSame(['old' => 0, 'new' => 1], $events[0]->changes['withdrawn']);
        $this->assertSame(['old' => 1, 'new' => 0], $events[1]->changes['withdrawn']);
    }

    public function test_deleting_a_model_records_deleted(): void
    {
        $model = GlassModelModelFactory::new()->create();
        Event::fake([RecordChanged::class]);

        app(ActorStack::class)->run(Actor::system('test'), fn () => $this->repository()->remove($this->repository()->get($model->id)));

        $events = $this->recorded();
        $this->assertCount(1, $events);
        $this->assertSame(ChangeKind::Deleted, $events[0]->kind);
    }

    public function test_fitting_and_unfitting_a_vehicle_record_a_vehicles_relation_change(): void
    {
        $model = GlassModelModelFactory::new()->create();
        $vehicle = VehicleModelFactory::new()->create();
        Event::fake([RecordChanged::class]);

        $this->change($model->id, fn (GlassModel $entity) => $entity->fitVehicles([$vehicle->id]));
        $this->change($model->id, fn (GlassModel $entity) => $entity->removeFitment($vehicle->id));

        $events = $this->recorded();
        $this->assertCount(2, $events);
        $this->assertSame([], $events[0]->changes);
        $this->assertSame(['vehicles' => ['added' => [$vehicle->id], 'removed' => []]], $events[0]->relationChanges);
        $this->assertSame(['vehicles' => ['added' => [], 'removed' => [$vehicle->id]]], $events[1]->relationChanges);
    }

    public function test_changing_equipment_records_an_equipment_relation_change(): void
    {
        $model = GlassModelModelFactory::new()->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create();
        Event::fake([RecordChanged::class]);

        $this->change($model->id, fn (GlassModel $entity) => $entity->changeParameters($entity->positionId(), null, [$equipment->id], []));

        $this->assertSame(['equipment' => ['added' => [$equipment->id], 'removed' => []]], $this->recorded()[0]->relationChanges);
    }

    public function test_adding_and_removing_a_eurocode_record_the_code_on_the_model(): void
    {
        $model = GlassModelModelFactory::new()->withMainEurocode('1111AAA')->create();
        Event::fake([RecordChanged::class]);

        $this->change($model->id, fn (GlassModel $entity) => $entity->addEurocode(Eurocode::from('2222BBB'), null));
        $this->change($model->id, fn (GlassModel $entity) => $entity->removeEurocode(Eurocode::from('2222BBB')));

        $events = $this->recorded();
        $this->assertCount(2, $events);
        $this->assertSame($model->id, $events[0]->id);
        $this->assertSame(['eurocodes' => ['added' => ['2222BBB'], 'removed' => []]], $events[0]->relationChanges);
        $this->assertSame(['eurocodes' => ['added' => [], 'removed' => ['2222BBB']]], $events[1]->relationChanges);
    }

    public function test_changing_the_main_eurocode_records_main_eurocode_as_new_added_old_removed(): void
    {
        $model = GlassModelModelFactory::new()->withMainEurocode('1111AAA')->create();
        $this->change($model->id, fn (GlassModel $entity) => $entity->addEurocode(Eurocode::from('2222BBB'), null));
        Event::fake([RecordChanged::class]);

        $this->change($model->id, fn (GlassModel $entity) => $entity->changeMainEurocode(Eurocode::from('2222BBB')));

        $events = $this->recorded();
        $this->assertCount(1, $events);
        $this->assertSame(['main_eurocode' => ['added' => ['2222BBB'], 'removed' => ['1111AAA']]], $events[0]->relationChanges);
    }

    public function test_changing_oem_codes_records_each_code_as_a_string_even_when_digits_only(): void
    {
        $model = GlassModelModelFactory::new()->create();
        $this->change($model->id, fn (GlassModel $entity) => $entity->changeParameters($entity->positionId(), null, [], [OemCode::tryFrom('896-028-196')]));
        Event::fake([RecordChanged::class]);

        $this->change($model->id, fn (GlassModel $entity) => $entity->changeParameters($entity->positionId(), null, [], [OemCode::tryFrom('A-1')]));

        $this->assertSame(['oem_codes' => ['added' => ['A-1'], 'removed' => ['896-028-196']]], $this->recorded()[0]->relationChanges);
    }

    public function test_a_save_without_change_records_nothing(): void
    {
        $model = GlassModelModelFactory::new()->create();
        Event::fake([RecordChanged::class]);

        $this->change($model->id, fn (GlassModel $entity) => null);

        $this->assertSame([], $this->recorded());
    }
}
