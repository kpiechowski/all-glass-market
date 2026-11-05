<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Infrastructure\Bus;

use App\Core\Domain\Actor;
use App\Core\Domain\Events\ChangeKind;
use App\Core\Domain\Events\RecordChanged;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Core\Infrastructure\Bus\HoldCommandActor;
use App\Core\Infrastructure\Bus\WrapCommandInTransaction;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Modelling\CommandBus;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\Fixtures\Core\RecordWidgetCommand;
use Tests\Fixtures\Core\RecordWidgetHandler;
use Tests\TestCase;

class RecordedChangesThroughTheBusTest extends TestCase
{
    private CommandBus $bus;

    /** @var list<RecordChanged> */
    private array $events = [];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('widgets', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('stock')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('widget_tags', function (Blueprint $table): void {
            $table->unsignedBigInteger('widget_id');
            $table->unsignedBigInteger('tag_id');
        });

        Event::listen(RecordChanged::class, function (RecordChanged $event): void {
            $this->events[] = $event;
        });

        $this->bus = EcotoneLite::bootstrapFlowTesting(
            [RecordWidgetHandler::class, HoldCommandActor::class, WrapCommandInTransaction::class],
            [new RecordWidgetHandler, new HoldCommandActor(app(ActorStack::class)), new WrapCommandInTransaction],
        )->getGateway(CommandBus::class);
    }

    public function test_fields_and_relation_sync_on_a_created_record_give_one_created_event_with_both(): void
    {
        $actor = Actor::user(5);

        $this->bus->send(new RecordWidgetCommand($actor));

        $this->assertCount(1, $this->events);
        $this->assertSame(ChangeKind::Created, $this->events[0]->kind);
        $this->assertSame('cog', $this->events[0]->changes['name']['new']);
        $this->assertSame(['tags' => ['added' => [1, 2], 'removed' => []]], $this->events[0]->relationChanges);
        $this->assertSame($actor, $this->events[0]->actor);
    }

    public function test_a_nested_command_flushes_its_own_events_with_the_inner_actor(): void
    {
        $outer = Actor::user(5);

        $this->bus->send(new RecordWidgetCommand($outer, nested: true));

        $this->assertCount(2, $this->events);
        $this->assertSame('inner job', $this->events[0]->actor->reason);
        $this->assertSame($outer, $this->events[1]->actor);
    }

    public function test_a_command_that_throws_dispatches_nothing_and_writes_nothing(): void
    {
        try {
            $this->bus->send(new RecordWidgetCommand(Actor::user(5), fail: true));
            $this->fail('The handler exception was swallowed');
        } catch (RuntimeException) {
        }

        $this->assertSame([], $this->events);
        $this->assertSame(0, DB::table('widgets')->count());
    }

    public function test_a_throwing_listener_at_flush_rolls_the_command_back(): void
    {
        Event::listen(RecordChanged::class, fn () => throw new RuntimeException('cannot store entry'));

        try {
            $this->bus->send(new RecordWidgetCommand(Actor::user(5)));
            $this->fail('The listener failure was swallowed');
        } catch (RuntimeException) {
        }

        $this->assertSame(0, DB::table('widgets')->count());
        $this->assertSame(0, DB::table('widget_tags')->count());
    }
}
