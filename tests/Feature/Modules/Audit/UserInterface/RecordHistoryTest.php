<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\UserInterface;

use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Application\Queries\PresentChange\PresentedLine;
use App\Modules\Audit\Application\Queries\PresentChange\PresentedValue;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Audit\UserInterface\Filament\RelationManagers\RecordHistoryRelationManager;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ViewUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class RecordHistoryTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $subject;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->subject = UserModel::withoutEvents(fn (): UserModel => UserModel::factory()->create());
    }

    private function history(): Testable
    {
        return Livewire::test(RecordHistoryRelationManager::class, [
            'ownerRecord' => $this->subject,
            'pageClass' => ViewUser::class,
            'recordType' => 'users.user',
        ]);
    }

    /** @param array<string, mixed> $state */
    private function entry(array $state = []): ChangeModel
    {
        return ChangeModel::factory()->forRecord('users.user', $this->subject->id)->create($state);
    }

    public function test_it_lists_the_record_entries_newest_first_and_only_that_record(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $old = $this->entry(['occurred_at' => now()->subDays(2)]);
        $new = $this->entry(['occurred_at' => now()]);
        $other = ChangeModel::factory()->forRecord('users.user', $this->subject->id + 1)->create();

        $this->history()
            ->assertCanSeeTableRecords([$new, $old], inOrder: true)
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_it_shows_the_actor_name_or_system_with_reason(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $actor = UserModel::withoutEvents(fn () => UserModel::factory()->create(['name' => 'Jan Admin']));
        $this->entry(['actor_id' => $actor->id]);
        ChangeModel::factory()->forRecord('users.user', $this->subject->id)->bySystem('Harmonogram')->create();

        $this->history()->assertSee(['Jan Admin', 'System: Harmonogram']);
    }

    public function test_it_filters_by_actor_and_by_kind(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $byUser = $this->entry();
        $bySystem = ChangeModel::factory()->forRecord('users.user', $this->subject->id)->bySystem()->create();
        $deleted = $this->entry(['kind' => ChangeKind::Deleted, 'changes' => []]);

        $this->history()
            ->filterTable('actor', 'system')
            ->assertCanSeeTableRecords([$bySystem])
            ->assertCanNotSeeTableRecords([$byUser])
            ->removeTableFilters()
            ->filterTable('actor', (string) $byUser->actor_id)
            ->assertCanSeeTableRecords([$byUser])
            ->assertCanNotSeeTableRecords([$bySystem])
            ->removeTableFilters()
            ->filterTable('kind', 'deleted')
            ->assertCanSeeTableRecords([$deleted])
            ->assertCanNotSeeTableRecords([$byUser, $bySystem]);
    }

    public function test_the_details_show_date_actor_kind_and_one_line_per_change(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $entry = $this->entry([
            'changes' => ['name' => ['old' => 'Stare', 'new' => 'Nowe'], 'ghost_field' => ['old' => 'x', 'new' => 'y']],
            'relation_changes' => ['extra_permissions' => ['added' => ['users.impersonate'], 'removed' => []]],
            'occurred_at' => '2026-03-04 10:30:00',
        ]);

        $this->history()
            ->mountTableAction('details', $entry)
            ->assertMountedActionModalSee(['04.03.2026 10:30', 'Zmieniono', 'Stare', 'Nowe', 'ghost_field', 'Wchodzenie na konta innych użytkowników']);
    }

    public function test_the_details_mark_a_removed_reference(): void
    {
        $html = view('audit::change-details', [
            'entry' => ChangeModel::factory()->make(['occurred_at' => now()]),
            'actor' => 'Jan',
            'lines' => [new PresentedLine('Warsztat', new PresentedValue('Stary warsztat', removed: true), new PresentedValue('—'))],
        ])->render();

        $this->assertStringContainsString('Stary warsztat', $html);
        $this->assertStringContainsString('usunięto', $html);
    }

    public function test_the_record_type_cannot_be_set_from_the_client(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());

        $this->expectException(CannotUpdateLockedPropertyException::class);

        $this->history()->set('recordType', 'users.role');
    }

    public function test_it_is_read_only(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $entry = $this->entry();

        $this->history()
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete')
            ->assertTableActionExists('details', record: $entry)
            ->assertTableBulkActionDoesNotExist('delete')
            ->assertSuccessful();

        $this->assertTrue(RecordHistoryRelationManager::make(['recordType' => 'users.user'])->relationManager === RecordHistoryRelationManager::class);
    }

    public function test_the_user_detail_mounts_the_section_for_an_admin(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());

        Livewire::test(ViewUser::class, ['record' => $this->subject->getKey()])
            ->assertSeeLivewire(RecordHistoryRelationManager::class);
    }

    public function test_the_user_detail_does_not_mount_the_section_without_the_permission(): void
    {
        $this->actingAs(UserModel::factory()->withRole('user')->create(['extra_permissions' => ['users.manage']]));

        Livewire::test(ViewUser::class, ['record' => $this->subject->getKey()])
            ->assertDontSeeLivewire(RecordHistoryRelationManager::class);
    }

    public function test_it_is_hidden_without_the_permission(): void
    {
        $this->actingAs(UserModel::factory()->create());

        $this->assertFalse(RecordHistoryRelationManager::canViewForRecord($this->subject, ViewUser::class));
    }

    public function test_a_seller_viewing_their_own_page_cannot_see_it(): void
    {
        $seller = UserModel::factory()->create();
        $this->actingAs($seller);

        $this->assertFalse(RecordHistoryRelationManager::canViewForRecord($seller, ViewUser::class));
    }

    public function test_an_extra_permission_shows_it_without_a_role_change(): void
    {
        $user = UserModel::factory()->create(['extra_permissions' => ['audit.record_history']]);
        $this->actingAs($user);

        $this->assertTrue(RecordHistoryRelationManager::canViewForRecord($this->subject, ViewUser::class));
    }
}
