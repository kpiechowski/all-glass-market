<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\UserInterface;

use App\Core\Domain\Actor;
use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Audit\UserInterface\Filament\Pages\HistoryAndLogs\HistoryAndLogs;
use App\Modules\Audit\UserInterface\Filament\RelationManagers\RecordHistoryRelationManager;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ViewUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Fixtures\Audit\ChangeThingCommand;
use Tests\Fixtures\Audit\RegistersThings;
use Tests\Fixtures\Audit\ThingModel;
use Tests\TestCase;

/** FR-13, FR-14 in the UI, on the test-only "Rzecz" type. */
class RestoreActionTest extends TestCase
{
    use RefreshDatabase, RegistersThings;

    private int $thingId;

    private ChangeModel $changed;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->setUpThings();
        $system = Actor::system('seed');
        $this->thingId = $this->createThing($system, ['name' => 'szyba', 'price' => 1200, 'status' => 'draft']);
        $this->thingBus->send(new ChangeThingCommand($system, 'update', $this->thingId, ['price' => 1500, 'status' => 'active']));
        $this->changed = ChangeModel::query()->where('kind', ChangeKind::Changed)->sole();
    }

    private function history(?UserModel $as = null): Testable
    {
        $this->actingAs($as ?? UserModel::factory()->superAdmin()->create());

        return Livewire::test(RecordHistoryRelationManager::class, [
            'ownerRecord' => ThingModel::withTrashed()->findOrFail($this->thingId),
            'pageClass' => ViewUser::class,
            'recordType' => 'things.thing',
        ]);
    }

    private function entry(array $state): ChangeModel
    {
        return ChangeModel::factory()->forRecord('things.thing', $this->thingId)->create($state);
    }

    public function test_it_is_shown_on_a_changed_entry_with_a_restorable_field(): void
    {
        $this->history()->assertTableActionVisible('restore', $this->changed);
    }

    public function test_it_is_shown_on_the_central_page_too(): void
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        Livewire::test(HistoryAndLogs::class)->assertTableActionVisible('restore', $this->changed);
    }

    public function test_it_is_hidden_on_created_deleted_and_relation_only_entries_and_unrestorable_fields(): void
    {
        $created = ChangeModel::query()->where('kind', ChangeKind::Created)->sole();
        $deleted = $this->entry(['kind' => ChangeKind::Deleted, 'changes' => []]);
        $relationOnly = $this->entry(['changes' => [], 'relation_changes' => ['producers' => ['added' => [1], 'removed' => []]]]);
        $nameOnly = $this->entry(['changes' => ['name' => ['old' => 'a', 'new' => 'b']]]);

        $history = $this->history();

        foreach ([$created, $deleted, $relationOnly, $nameOnly] as $entry) {
            $history->assertTableActionHidden('restore', $entry);
        }
    }

    public function test_it_is_hidden_when_the_record_no_longer_exists(): void
    {
        $this->thingBus->send(new ChangeThingCommand(Actor::system('seed'), 'delete', $this->thingId));

        $this->history()->assertTableActionHidden('restore', $this->changed);
    }

    public function test_it_is_hidden_on_a_type_that_offers_no_restore(): void
    {
        $entry = ChangeModel::factory()->forRecord('users.user', 1)->create(['changes' => ['price' => ['old' => 1, 'new' => 2]]]);

        $this->actingAs(UserModel::factory()->superAdmin()->create());

        Livewire::test(HistoryAndLogs::class)->assertTableActionHidden('restore', $entry);
    }

    public function test_it_is_hidden_without_the_permission_and_shown_with_it_as_an_extra_permission(): void
    {
        $this->history(UserModel::factory()->admin()->create())->assertTableActionHidden('restore', $this->changed);
        $this->history(UserModel::factory()->admin()->create(['extra_permissions' => ['audit.restore_changes']]))
            ->assertTableActionVisible('restore', $this->changed);
    }

    public function test_the_confirmation_lists_the_fields_and_values_to_be_written(): void
    {
        $this->history()
            ->mountTableAction('restore', $this->changed)
            ->assertMountedActionModalSee(['Przywrócić: Cena → 1 200 zł, Status → Szkic?']);
    }

    public function test_confirming_writes_the_old_values_and_marks_the_new_entry(): void
    {
        $this->history()->callTableAction('restore', $this->changed)->assertNotified();

        $thing = ThingModel::findOrFail($this->thingId);
        $this->assertSame(1200, $thing->price);
        $this->assertSame('draft', $thing->status);

        $marked = ChangeModel::query()->whereNotNull('restored_from_id')->sole();
        $this->assertSame($this->changed->id, $marked->restored_from_id);

        $this->history()
            ->mountTableAction('details', $marked)
            ->assertMountedActionModalSee('przywrócono z wpisu z '.$this->changed->occurred_at->format('d.m.Y H:i'));
    }

    public function test_a_refusal_by_the_module_leaves_the_record_unchanged_and_shows_the_reason(): void
    {
        $this->thingBus->send(new ChangeThingCommand(Actor::system('seed'), 'update', $this->thingId, ['price' => 5]));
        $source = $this->entry(['changes' => ['price' => ['old' => 20000, 'new' => 5]]]);
        $entries = ChangeModel::where('record_type', 'things.thing')->count();

        $this->history()->callTableAction('restore', $source)->assertNotified('Cena rzeczy nie może przekraczać 9999.');

        $this->assertSame(5, ThingModel::findOrFail($this->thingId)->price);
        $this->assertSame($entries, ChangeModel::where('record_type', 'things.thing')->count());
    }
}
