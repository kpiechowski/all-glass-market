<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\Application\Commands\RestoreChange;

use App\Core\Domain\Actor;
use App\Core\Domain\Events\ChangeKind;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Audit\Application\Commands\RestoreChange\RestoreChangeCommand;
use App\Modules\Audit\Domain\Exceptions\ChangeRefused;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\Audit\ChangeThingCommand;
use Tests\Fixtures\Audit\RegistersThings;
use Tests\Fixtures\Audit\ThingModel;
use Tests\Fixtures\Audit\ThingRefused;
use Tests\TestCase;

class RestoreChangeCommandHandlerTest extends TestCase
{
    use RefreshDatabase, RegistersThings;

    private int $thingId;

    private ChangeModel $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpThings();
        $system = Actor::system('seed');
        $this->thingId = $this->createThing($system, ['name' => 'szyba', 'price' => 100, 'status' => 'draft']);
        $this->thingBus->send(new ChangeThingCommand($system, 'update', $this->thingId, ['name' => 'lustro', 'price' => 200, 'status' => 'active']));
        $this->source = ChangeModel::query()->where('kind', ChangeKind::Changed)->sole();
    }

    private function superAdmin(): Actor
    {
        return Actor::user(UserModel::factory()->superAdmin()->create()->id);
    }

    private function restore(Actor $actor, ?int $changeId = null): void
    {
        $this->thingBus->send(new RestoreChangeCommand($actor, $changeId ?? $this->source->id));
    }

    public function test_it_writes_exactly_the_old_values_of_the_restorable_fields(): void
    {
        $this->restore($this->superAdmin());

        $thing = ThingModel::findOrFail($this->thingId);
        $this->assertSame(100, $thing->price);
        $this->assertSame('draft', $thing->status);
        $this->assertSame('lustro', $thing->name);
    }

    public function test_the_new_entry_is_a_marked_changed_entry_by_the_restoring_user(): void
    {
        $actor = $this->superAdmin();

        $this->restore($actor);

        $entry = ChangeModel::query()->where('kind', ChangeKind::Changed)->latest('id')->first();
        $this->assertNotSame($this->source->id, $entry->id);
        $this->assertSame($actor->id, $entry->actor_id);
        $this->assertSame($this->source->id, $entry->restored_from_id);
        $this->assertTrue($this->source->occurred_at->equalTo($entry->restored_from_at));
        $this->assertSame(['price' => ['old' => 200, 'new' => 100], 'status' => ['old' => 'active', 'new' => 'draft']], $entry->changes);
        $this->assertNull($this->source->fresh()->restored_from_id);
    }

    public function test_an_ordinary_edit_afterwards_carries_no_marker(): void
    {
        $this->restore($this->superAdmin());

        $this->thingBus->send(new ChangeThingCommand(Actor::system('seed'), 'update', $this->thingId, ['price' => 300]));

        $this->assertNull(ChangeModel::query()->latest('id')->first()->restored_from_id);
    }

    public function test_a_module_refusal_changes_nothing_and_carries_its_reason(): void
    {
        $system = Actor::system('seed');
        $id = $this->createThing($system, ['name' => 'taflowa', 'price' => 20000]);
        $this->thingBus->send(new ChangeThingCommand($system, 'update', $id, ['price' => 5]));
        $source = ChangeModel::query()->where('record_id', $id)->where('kind', ChangeKind::Changed)->sole();
        $actor = $this->superAdmin();
        $entries = ChangeModel::count();

        try {
            $this->restore($actor, $source->id);
            $this->fail('Expected the module to refuse');
        } catch (ThingRefused $e) {
            $this->assertSame('Cena rzeczy nie może przekraczać 9999.', $e->translationKey);
        }

        $this->assertSame(5, ThingModel::findOrFail($id)->price);
        $this->assertSame($entries, ChangeModel::count());
    }

    public function test_a_user_without_the_permission_is_refused(): void
    {
        $seller = Actor::user(UserModel::factory()->withRole('user')->create()->id);
        $admin = Actor::user(UserModel::factory()->admin()->create()->id);

        foreach ([$seller, $admin] as $actor) {
            try {
                $this->restore($actor);
                $this->fail('Expected a refusal');
            } catch (AuthorizationException) {
                $this->assertSame(200, ThingModel::findOrFail($this->thingId)->price);
            }
        }
    }

    public function test_an_admin_with_the_extra_permission_may_restore(): void
    {
        $admin = UserModel::factory()->admin()->create(['extra_permissions' => ['audit.restore_changes']]);

        $this->restore(Actor::user($admin->id));

        $this->assertSame(100, ThingModel::findOrFail($this->thingId)->price);
    }

    public function test_it_refuses_an_entry_that_cannot_be_restored(): void
    {
        $created = ChangeModel::query()->where('kind', ChangeKind::Created)->sole();
        $relationOnly = ChangeModel::factory()->forRecord('things.thing', $this->thingId)->create([
            'changes' => [], 'relation_changes' => ['producers' => ['added' => [1], 'removed' => []]],
        ]);
        $nameOnly = ChangeModel::factory()->forRecord('things.thing', $this->thingId)->create(['changes' => ['name' => ['old' => 'a', 'new' => 'b']]]);

        foreach ([$created->id, $relationOnly->id, $nameOnly->id] as $id) {
            try {
                $this->restore($this->superAdmin(), $id);
                $this->fail('Expected a refusal');
            } catch (ChangeRefused $e) {
                $this->assertSame('audit::exceptions.restore_nothing_restorable', $e->translationKey);
            }
        }

        $this->assertSame(200, ThingModel::findOrFail($this->thingId)->price);
    }

    public function test_it_refuses_a_deleted_record_a_missing_entry_and_a_type_without_restore(): void
    {
        $other = ChangeModel::factory()->forRecord('unregistered.type', 1)->create();
        $missing = $this->source->id + 999;
        $this->thingBus->send(new ChangeThingCommand(Actor::system('seed'), 'delete', $this->thingId));
        $expectations = [
            [$this->source->id, 'audit::exceptions.restore_record_gone'],
            [$other->id, 'audit::exceptions.restore_not_offered'],
            [$missing, 'audit::exceptions.restore_not_found'],
        ];

        foreach ($expectations as [$id, $key]) {
            try {
                $this->restore($this->superAdmin(), $id);
                $this->fail('Expected a refusal');
            } catch (ChangeRefused $e) {
                $this->assertSame($key, $e->translationKey);
            }
        }
    }
}
