<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence\Observers;

use App\Core\Domain\Actor;
use App\Core\Domain\Events\ChangeKind;
use App\Core\Domain\Events\RecordChanged;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Offers\Infrastructure\Factories\ReservationModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReservationObserverTest extends TestCase
{
    use RefreshDatabase;

    private function asActor(callable $callback): mixed
    {
        return app(ActorStack::class)->run(Actor::user(1), fn () => ReadOnlyModel::writing($callback));
    }

    public function test_creating_a_record_emits_one_record_changed_of_kind_created(): void
    {
        Event::fake([RecordChanged::class]);

        $model = $this->asActor(fn () => ReservationModelFactory::new()->create());

        Event::assertDispatched(
            RecordChanged::class,
            fn (RecordChanged $event) => $event->modelClass === ReservationModel::class
                && $event->id === $model->id
                && $event->kind === ChangeKind::Created,
        );
    }

    public function test_updating_a_record_emits_the_old_and_new_value(): void
    {
        $model = $this->asActor(fn () => ReservationModelFactory::new()->create());
        Event::fake([RecordChanged::class]);

        $this->asActor(fn () => $model->update(['note' => 'Nowa notatka']));

        Event::assertDispatched(
            RecordChanged::class,
            fn (RecordChanged $event) => $event->modelClass === ReservationModel::class
                && $event->kind === ChangeKind::Changed
                && $event->changes['note']['new'] === 'Nowa notatka',
        );
    }

    public function test_a_save_without_change_records_nothing(): void
    {
        $model = $this->asActor(fn () => ReservationModelFactory::new()->create());
        Event::fake([RecordChanged::class]);

        $this->asActor(fn () => $model->fresh()->save());

        Event::assertNotDispatched(RecordChanged::class);
    }
}
