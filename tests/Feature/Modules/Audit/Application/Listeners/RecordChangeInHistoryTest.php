<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\Application\Listeners;

use App\Core\Domain\Actor;
use App\Core\Domain\Events\RecordChanged;
use App\Modules\Audit\Domain\Repositories\ChangeRepository;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\Fixtures\Audit\ChangeThingCommand;
use Tests\Fixtures\Audit\FailingChangeRepository;
use Tests\Fixtures\Audit\RegistersThings;
use Tests\Fixtures\Audit\ThingModel;
use Tests\TestCase;

class RecordChangeInHistoryTest extends TestCase
{
    use RefreshDatabase, RegistersThings;

    public function test_a_command_that_writes_a_record_several_times_leaves_one_entry(): void
    {
        $this->setUpThings();

        $id = $this->createThing(Actor::system('seed'), ['name' => 'szyba']);
        DB::table('audit_changes')->delete();

        $this->thingBus->send(new ChangeThingCommand(Actor::system('seed'), 'update', $id, ['name' => 'lustro', 'price' => 5]));

        $this->assertSame(1, ChangeModel::count());
    }

    public function test_recording_an_entry_produces_no_record_changed_of_its_own(): void
    {
        $this->setUpThings();
        $seen = [];
        Event::listen(RecordChanged::class, function (RecordChanged $event) use (&$seen): void {
            $seen[] = $event->modelClass;
        });

        $this->createThing(Actor::system('seed'));

        $this->assertSame([ThingModel::class], $seen);
        $this->assertSame(1, ChangeModel::count());
    }

    public function test_an_unregistered_type_is_ignored(): void
    {
        $this->setUpThings(register: false);

        $this->createThing(Actor::system('seed'));

        $this->assertSame(0, ChangeModel::count());
    }

    public function test_when_recording_fails_the_whole_command_rolls_back(): void
    {
        $this->setUpThings();
        $this->app->bind(ChangeRepository::class, FailingChangeRepository::class);

        try {
            $this->createThing(Actor::system('seed'));
            $this->fail('Expected the command to fail');
        } catch (RuntimeException $e) {
            $this->assertSame('entry could not be stored', $e->getMessage());
        }

        $this->assertSame(0, ThingModel::withTrashed()->count());
        $this->assertSame(0, ChangeModel::count());
    }
}
