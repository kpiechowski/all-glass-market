<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Infrastructure\Persistence;

use App\Core\Domain\Actor;
use App\Core\Domain\Events\ChangeKind;
use App\Core\Domain\Events\RecordChanged;
use App\Core\Exceptions\NoCurrentActorException;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Core\Infrastructure\Persistence\ReadOnlyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\Fixtures\Core\RecordedWidgetModel;
use Tests\Fixtures\Core\RecordedWidgetRepository;
use Tests\TestCase;

class ChangeRecorderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('widgets', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('stock')->default(0);
            $table->timestamp('launched_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('widget_tags', function (Blueprint $table): void {
            $table->unsignedBigInteger('widget_id');
            $table->unsignedBigInteger('tag_id');
        });
    }

    private function asActor(Actor $actor, callable $callback): mixed
    {
        return app(ActorStack::class)->run($actor, fn () => ReadOnlyModel::writing($callback));
    }

    /** @return list<RecordChanged> */
    private function recorded(callable $callback): array
    {
        $events = [];
        Event::listen(RecordChanged::class, function (RecordChanged $event) use (&$events): void {
            $events[] = $event;
        });
        $callback();

        return $events;
    }

    public function test_creating_records_a_created_entry_with_new_values_and_the_actor(): void
    {
        $actor = Actor::user(4);

        $events = $this->recorded(fn () => $this->asActor($actor, fn () => RecordedWidgetModel::create(['name' => 'gear'])));

        $this->assertCount(1, $events);
        $this->assertSame(ChangeKind::Created, $events[0]->kind);
        $this->assertSame(['old' => null, 'new' => 'gear'], $events[0]->changes['name']);
        $this->assertSame($actor, $events[0]->actor);
    }

    public function test_changing_records_old_and_new_values(): void
    {
        $model = $this->asActor(Actor::user(1), fn () => RecordedWidgetModel::create(['name' => 'gear']));

        $events = $this->recorded(fn () => $this->asActor(Actor::user(2), fn () => $model->update(['name' => 'cog'])));

        $this->assertCount(1, $events);
        $this->assertSame(ChangeKind::Changed, $events[0]->kind);
        $this->assertSame(['name' => ['old' => 'gear', 'new' => 'cog']], array_intersect_key($events[0]->changes, ['name' => 1]));
        $this->assertSame(2, $events[0]->actor->id);
    }

    public function test_deleting_records_a_deleted_entry(): void
    {
        $model = $this->asActor(Actor::user(1), fn () => RecordedWidgetModel::create(['name' => 'gear']));

        $events = $this->recorded(fn () => $this->asActor(Actor::user(1), fn () => $model->delete()));

        $this->assertCount(1, $events);
        $this->assertSame(ChangeKind::Deleted, $events[0]->kind);
        $this->assertSame($model->id, $events[0]->id);
    }

    public function test_restoring_records_exactly_one_restored_entry(): void
    {
        $model = $this->asActor(Actor::user(1), fn () => RecordedWidgetModel::create(['name' => 'gear']));
        $this->asActor(Actor::user(1), fn () => $model->delete());

        $events = $this->recorded(fn () => $this->asActor(Actor::user(1), fn () => $model->restore()));

        $this->assertCount(1, $events);
        $this->assertSame(ChangeKind::Restored, $events[0]->kind);
    }

    public function test_a_write_outside_a_command_fails_loudly(): void
    {
        $this->expectException(NoCurrentActorException::class);

        ReadOnlyModel::writing(fn () => RecordedWidgetModel::create(['name' => 'gear']));
    }

    public function test_a_failing_listener_rolls_back_the_change_in_the_same_transaction(): void
    {
        Event::listen(RecordChanged::class, fn () => throw new RuntimeException('cannot store entry'));

        try {
            DB::transaction(fn () => $this->asActor(Actor::user(1), fn () => RecordedWidgetModel::create(['name' => 'gear'])));
            $this->fail('The listener failure was swallowed');
        } catch (RuntimeException) {
        }

        $this->assertSame(0, DB::table('widgets')->count());
    }

    public function test_the_relation_sync_reports_added_and_removed_ids(): void
    {
        $id = $this->asActor(Actor::user(1), fn () => RecordedWidgetModel::create(['name' => 'gear']))->id;
        $repository = new RecordedWidgetRepository;
        $actor = Actor::user(9);
        $this->asActor($actor, fn () => $repository->syncTags($id, [1, 2]));

        $events = $this->recorded(fn () => $this->asActor($actor, fn () => $repository->syncTags($id, [2, 3])));

        $this->assertCount(1, $events);
        $this->assertSame(ChangeKind::Changed, $events[0]->kind);
        $this->assertSame([], $events[0]->changes);
        $this->assertSame(['tags' => ['added' => [3], 'removed' => [1]]], $events[0]->relationChanges);
        $this->assertSame($actor, $events[0]->actor);
        $this->assertEqualsCanonicalizing([2, 3], DB::table('widget_tags')->pluck('tag_id')->all());
    }

    public function test_the_relation_sync_emits_nothing_when_nothing_changed(): void
    {
        $id = $this->asActor(Actor::user(1), fn () => RecordedWidgetModel::create(['name' => 'gear']))->id;
        $repository = new RecordedWidgetRepository;
        $this->asActor(Actor::user(1), fn () => $repository->syncTags($id, [1]));

        $events = $this->recorded(fn () => $this->asActor(Actor::user(1), fn () => $repository->syncTags($id, [1])));

        $this->assertSame([], $events);
    }

    public function test_fields_and_a_relation_sync_on_one_record_in_one_command_give_one_event(): void
    {
        $id = $this->asActor(Actor::user(1), fn () => RecordedWidgetModel::create(['name' => 'gear']))->id;
        $repository = new RecordedWidgetRepository;

        $events = $this->recorded(fn () => $this->asActor(Actor::user(1), function () use ($id, $repository): void {
            RecordedWidgetModel::findOrFail($id)->update(['name' => 'cog']);
            $repository->syncTags($id, [4]);
        }));

        $this->assertCount(1, $events);
        $this->assertSame(['old' => 'gear', 'new' => 'cog'], $events[0]->changes['name']);
        $this->assertSame(['tags' => ['added' => [4], 'removed' => []]], $events[0]->relationChanges);
    }

    public function test_update_then_delete_in_one_command_give_changed_then_deleted(): void
    {
        $id = $this->asActor(Actor::user(1), fn () => RecordedWidgetModel::create(['name' => 'gear']))->id;

        $events = $this->recorded(fn () => $this->asActor(Actor::user(1), function () use ($id): void {
            $model = RecordedWidgetModel::findOrFail($id);
            $model->update(['name' => 'cog']);
            $model->delete();
        }));

        $this->assertSame([ChangeKind::Changed, ChangeKind::Deleted], array_map(fn (RecordChanged $e) => $e->kind, $events));
    }

    public function test_nothing_is_dispatched_when_the_command_throws(): void
    {
        $events = $this->recorded(function (): void {
            try {
                $this->asActor(Actor::user(1), function (): void {
                    RecordedWidgetModel::create(['name' => 'gear']);

                    throw new RuntimeException('boom');
                });
            } catch (RuntimeException) {
            }
        });

        $this->assertSame([], $events);
    }

    public function test_old_and_new_values_are_both_raw_even_for_a_cast_field(): void
    {
        $widget = $this->asActor(Actor::user(1), fn () => RecordedWidgetModel::create(['name' => 'gear', 'launched_at' => '2026-01-02 03:04:05']));

        $events = $this->recorded(fn () => $this->asActor(Actor::user(1), fn () => $widget->fresh()->update(['launched_at' => '2026-02-03 04:05:06'])));

        $this->assertSame(
            ['launched_at' => ['old' => '2026-01-02 03:04:05', 'new' => '2026-02-03 04:05:06']],
            $events[0]->changes,
        );
    }
}
