<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\Application\Commands\RecordChange;

use App\Core\Domain\Actor;
use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Application\Commands\RecordChange\RecordChangeCommand;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\Audit\ChangeThingCommand;
use Tests\Fixtures\Audit\RegistersThings;
use Tests\Fixtures\Audit\ThingModel;
use Tests\TestCase;

class RecordChangeCommandHandlerTest extends TestCase
{
    use RefreshDatabase, RegistersThings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpThings();
    }

    public function test_a_created_record_gives_one_entry_with_its_initial_audited_values(): void
    {
        $user = UserModel::factory()->create();

        $id = $this->createThing(Actor::user($user->id), ['name' => 'szyba', 'price' => 1200, 'secret' => 'x']);

        $entry = ChangeModel::sole();
        $this->assertSame('things.thing', $entry->record_type);
        $this->assertSame($id, $entry->record_id);
        $this->assertSame(ChangeKind::Created, $entry->kind);
        $this->assertSame(['name' => ['old' => null, 'new' => 'szyba'], 'price' => ['old' => null, 'new' => 1200]], $entry->changes);
        $this->assertNull($entry->title);
    }

    public function test_a_changed_record_gives_one_entry_with_old_and_new_raw_values(): void
    {
        $id = $this->createThing(Actor::system('seed'), ['name' => 'szyba', 'price' => 1200]);
        DB::table('audit_changes')->delete();

        $this->thingBus->send(new ChangeThingCommand(Actor::system('seed'), 'update', $id, ['price' => 1500]));

        $entry = ChangeModel::sole();
        $this->assertSame(ChangeKind::Changed, $entry->kind);
        $this->assertSame(['price' => ['old' => 1200, 'new' => 1500]], $entry->changes);
    }

    public function test_excluded_fields_are_dropped_from_the_entry(): void
    {
        $id = $this->createThing(Actor::system('seed'), ['name' => 'szyba']);
        DB::table('audit_changes')->delete();

        $this->thingBus->send(new ChangeThingCommand(Actor::system('seed'), 'update', $id, ['name' => 'lustro', 'secret' => 'hunter2']));

        $entry = ChangeModel::sole();
        $this->assertSame(['name'], array_keys($entry->changes));
    }

    public function test_technical_columns_are_never_recorded_even_when_a_module_lists_none(): void
    {
        $id = $this->createThing(Actor::system('seed'), ['name' => 'szyba']);

        $this->assertSame(['name'], array_keys(ChangeModel::sole()->changes));

        DB::table('audit_changes')->delete();
        $this->thingBus->send(new ChangeThingCommand(Actor::system('seed'), 'update', $id, ['updated_at' => now()->addDay()]));

        $this->assertSame(0, ChangeModel::count());
    }

    public function test_a_change_to_unaudited_fields_only_gives_no_entry(): void
    {
        $id = $this->createThing(Actor::system('seed'), ['name' => 'szyba']);
        DB::table('audit_changes')->delete();

        $this->thingBus->send(new ChangeThingCommand(Actor::system('seed'), 'update', $id, ['secret' => 'x', 'synced_at' => now()]));

        $this->assertSame(0, ChangeModel::count());
    }

    public function test_a_relation_change_is_recorded_as_items_added_and_removed(): void
    {
        $a = $this->producer('A');
        $b = $this->producer('B');
        $c = $this->producer('C');
        $id = $this->createThing(Actor::system('seed'), ['name' => 'szyba'], [$a, $b]);
        DB::table('audit_changes')->delete();

        $this->thingBus->send(new ChangeThingCommand(Actor::system('seed'), 'sync', $id, producerIds: [$b, $c]));

        $entry = ChangeModel::sole();
        $this->assertSame(ChangeKind::Changed, $entry->kind);
        $this->assertSame([], $entry->changes);
        $this->assertSame(['producers' => ['added' => [$c], 'removed' => [$a]]], $entry->relation_changes);
    }

    public function test_a_relation_that_is_not_tracked_is_dropped(): void
    {
        $this->createThing(Actor::system('seed'), ['name' => 'szyba']);
        DB::table('audit_changes')->delete();

        $this->app->make(CommandBus::class)->send(new RecordChangeCommand(
            Actor::system('seed'), ThingModel::class, 1, 'changed', [], ['secrets' => ['added' => [1], 'removed' => []]],
        ));

        $this->assertSame(0, ChangeModel::count());
    }

    public function test_a_deleted_record_gives_an_entry_with_its_last_title_and_no_values(): void
    {
        $id = $this->createThing(Actor::system('seed'), ['name' => 'szyba']);
        DB::table('audit_changes')->delete();

        $this->thingBus->send(new ChangeThingCommand(Actor::system('seed'), 'delete', $id));

        $entry = ChangeModel::sole();
        $this->assertSame(ChangeKind::Deleted, $entry->kind);
        $this->assertSame('Rzecz szyba', $entry->title);
        $this->assertSame([], $entry->changes);
    }

    public function test_a_record_brought_back_gives_a_restored_entry(): void
    {
        $id = $this->createThing(Actor::system('seed'), ['name' => 'szyba']);
        $this->thingBus->send(new ChangeThingCommand(Actor::system('seed'), 'delete', $id));
        DB::table('audit_changes')->delete();

        $this->thingBus->send(new ChangeThingCommand(Actor::system('seed'), 'restore', $id));

        $entry = ChangeModel::sole();
        $this->assertSame(ChangeKind::Restored, $entry->kind);
        $this->assertSame([], $entry->changes);
    }

    public function test_the_entry_belongs_to_the_user_who_acted(): void
    {
        $user = UserModel::factory()->create();

        $this->createThing(Actor::user($user->id));

        $entry = ChangeModel::sole();
        $this->assertSame($user->id, $entry->actor_id);
        $this->assertNull($entry->actor_reason);
    }

    public function test_the_entry_belongs_to_the_system_with_its_reason(): void
    {
        $this->createThing(Actor::system('Import WooCommerce'));

        $entry = ChangeModel::sole();
        $this->assertNull($entry->actor_id);
        $this->assertSame('Import WooCommerce', $entry->actor_reason);
    }

    public function test_an_unregistered_record_type_gives_no_entry(): void
    {
        $result = $this->app->make(CommandBus::class)->send(new RecordChangeCommand(
            Actor::system('seed'), 'App\\Unknown', 1, 'created', ['name' => ['old' => null, 'new' => 'x']], [],
        ));

        $this->assertNull($result);
        $this->assertSame(0, ChangeModel::count());
    }

    private function producer(string $name): int
    {
        return DB::table('audit_producers')->insertGetId(['name' => $name]);
    }
}
