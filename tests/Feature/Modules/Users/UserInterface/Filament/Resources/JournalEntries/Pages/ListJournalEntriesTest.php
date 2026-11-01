<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Pages;

use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Pages\ListJournalEntries;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListJournalEntriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_a_user_sees_only_their_own_entries_newest_first(): void
    {
        $me = UserModel::factory()->create();
        $other = UserModel::factory()->create();
        $older = JournalEntryModel::factory()->create(['user_id' => $me->id, 'created_at' => now()->subDay()]);
        $newer = JournalEntryModel::factory()->create(['user_id' => $me->id, 'created_at' => now()]);
        $foreign = JournalEntryModel::factory()->create(['user_id' => $other->id]);

        $this->actingAs($me);

        Livewire::test(ListJournalEntries::class)
            ->assertCanSeeTableRecords([$newer, $older], inOrder: true)
            ->assertCanNotSeeTableRecords([$foreign]);
    }

    public function test_an_admin_sees_only_their_own_journal(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $foreign = JournalEntryModel::factory()->create();

        $this->actingAs($admin);

        Livewire::test(ListJournalEntries::class)->assertCanNotSeeTableRecords([$foreign]);
    }

    public function test_the_unread_count_shows_on_the_navigation_item(): void
    {
        $me = UserModel::factory()->create();
        JournalEntryModel::factory()->count(2)->create(['user_id' => $me->id]);
        JournalEntryModel::factory()->read()->create(['user_id' => $me->id]);
        JournalEntryModel::factory()->create();

        $this->actingAs($me);

        $this->assertSame('2', JournalEntryResource::getNavigationBadge());
    }

    public function test_entries_filter_by_kind_and_by_severity(): void
    {
        $me = UserModel::factory()->create();
        $offer = JournalEntryModel::factory()->create(['user_id' => $me->id, 'kind' => 'offer', 'severity' => JournalSeverity::Info]);
        $users = JournalEntryModel::factory()->create(['user_id' => $me->id, 'kind' => 'users', 'severity' => JournalSeverity::Warning]);

        $this->actingAs($me);

        Livewire::test(ListJournalEntries::class)
            ->filterTable('kind', 'users')
            ->assertCanSeeTableRecords([$users])
            ->assertCanNotSeeTableRecords([$offer])
            ->removeTableFilters()
            ->filterTable('severity', 'info')
            ->assertCanSeeTableRecords([$offer])
            ->assertCanNotSeeTableRecords([$users]);
    }

    public function test_the_owner_marks_an_entry_read(): void
    {
        $me = UserModel::factory()->create();
        $entry = JournalEntryModel::factory()->create(['user_id' => $me->id]);

        $this->actingAs($me);

        Livewire::test(ListJournalEntries::class)->callAction(TestAction::make('mark_read')->table($entry));

        $this->assertTrue($entry->fresh()->is_read);
    }

    public function test_the_owner_deletes_one_entry_and_many_but_never_creates_one(): void
    {
        $me = UserModel::factory()->create();
        [$one, $two, $three] = JournalEntryModel::factory()->count(3)->create(['user_id' => $me->id]);

        $this->actingAs($me);

        Livewire::test(ListJournalEntries::class)
            ->callAction(TestAction::make('delete')->table($one))
            ->callAction(TestAction::make('delete')->table($two));
        $this->assertSame([$three->id], JournalEntryModel::pluck('id')->all());

        $more = JournalEntryModel::factory()->count(2)->create(['user_id' => $me->id]);
        Livewire::test(ListJournalEntries::class)
            ->selectTableRecords($more->pluck('id')->all())
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertSame([$three->id], JournalEntryModel::pluck('id')->all());
        $this->assertFalse(JournalEntryResource::canCreate());
    }
}
