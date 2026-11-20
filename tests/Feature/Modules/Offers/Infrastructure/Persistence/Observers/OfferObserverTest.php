<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence\Observers;

use App\Core\Domain\Actor;
use App\Core\Domain\Events\ChangeKind;
use App\Core\Domain\Events\RecordChanged;
use App\Core\Exceptions\NoCurrentActorException;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OfferObserverTest extends TestCase
{
    use RefreshDatabase;

    private function asActor(Actor $actor, callable $callback): mixed
    {
        return app(ActorStack::class)->run($actor, fn () => ReadOnlyModel::writing($callback));
    }

    public function test_creating_a_record_emits_record_changed_with_kind_new_values_and_actor(): void
    {
        Event::fake([RecordChanged::class]);
        $actor = Actor::user(1);

        $id = $this->asActor($actor, fn () => OfferModelFactory::new()->create(['number' => 'AG00000001']))->id;

        Event::assertDispatched(
            RecordChanged::class,
            fn (RecordChanged $event) => $event->modelClass === OfferModel::class
                && $event->id === $id
                && $event->kind === ChangeKind::Created
                && $event->changes['number'] === ['old' => null, 'new' => 'AG00000001']
                && $event->actor === $actor,
        );
    }

    public function test_updating_a_record_emits_the_old_and_new_value(): void
    {
        $model = $this->asActor(Actor::user(1), fn () => OfferModelFactory::new()->create(['number' => 'AG00000001']));
        Event::fake([RecordChanged::class]);

        $this->asActor(Actor::user(1), fn () => $model->update(['identification_hint' => 'cog']));

        Event::assertDispatched(
            RecordChanged::class,
            fn (RecordChanged $event) => $event->kind === ChangeKind::Changed
                && $event->changes['identification_hint'] === ['old' => null, 'new' => 'cog'],
        );
    }

    public function test_deleting_a_record_emits_a_deleted_change(): void
    {
        $model = $this->asActor(Actor::user(1), fn () => OfferModelFactory::new()->create());
        Event::fake([RecordChanged::class]);

        $this->asActor(Actor::user(1), fn () => $model->delete());

        Event::assertDispatched(RecordChanged::class, fn (RecordChanged $event) => $event->kind === ChangeKind::Deleted);
    }

    public function test_a_write_outside_a_command_fails(): void
    {
        $this->expectException(NoCurrentActorException::class);

        ReadOnlyModel::writing(fn () => OfferModel::create(OfferModelFactory::new()->raw()));
    }
}
