<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\UserInterface;

use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Audit\UserInterface\Filament\Pages\AuditLog\AuditLog;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    private function page(): Testable
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        return Livewire::test(AuditLog::class);
    }

    private function recordUrl(Testable $page, ChangeModel $entry): ?string
    {
        return $page->instance()->getTable()->getColumn('record')->record($entry)->getUrl();
    }

    /** @return list<string> */
    private function navigationLabels(): array
    {
        return collect(Filament::getNavigation())
            ->flatMap(fn ($group) => $group->getItems())
            ->map(fn ($item): string => (string) $item->getLabel())
            ->values()->all();
    }

    private function subject(): UserModel
    {
        return UserModel::withoutEvents(fn (): UserModel => UserModel::factory()->create(['name' => 'Anna Nowak']));
    }

    public function test_it_lists_every_entry_newest_first(): void
    {
        $old = ChangeModel::factory()->create(['occurred_at' => now()->subDay()]);
        $new = ChangeModel::factory()->create(['occurred_at' => now()]);

        $this->page()->assertCanSeeTableRecords([$new, $old], inOrder: true);
    }

    public function test_it_filters_by_record_type(): void
    {
        $user = ChangeModel::factory()->forRecord('users.user', 1)->create();
        $other = ChangeModel::factory()->forRecord('offers.offer', 1)->create();

        $this->page()
            ->filterTable('record_type', 'users.user')
            ->assertCanSeeTableRecords([$user])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_it_offers_the_registered_types_in_the_filter(): void
    {
        $this->page()->assertTableFilterExists('record_type', fn ($filter): bool => array_key_exists('users.user', $filter->getOptions()));
    }

    public function test_it_filters_by_one_record(): void
    {
        $one = ChangeModel::factory()->forRecord('users.user', 7)->create();
        $other = ChangeModel::factory()->forRecord('users.user', 8)->create();

        $this->page()
            ->filterTable('record', ['record_id' => 7])
            ->assertCanSeeTableRecords([$one])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_it_filters_by_actor_and_by_system(): void
    {
        $byUser = ChangeModel::factory()->create();
        $bySystem = ChangeModel::factory()->bySystem()->create();

        $this->page()
            ->filterTable('actor', 'system')
            ->assertCanSeeTableRecords([$bySystem])
            ->assertCanNotSeeTableRecords([$byUser])
            ->removeTableFilters()
            ->filterTable('actor', (string) $byUser->actor_id)
            ->assertCanSeeTableRecords([$byUser])
            ->assertCanNotSeeTableRecords([$bySystem]);
    }

    public function test_it_filters_by_kind(): void
    {
        $changed = ChangeModel::factory()->create();
        $deleted = ChangeModel::factory()->create(['kind' => ChangeKind::Deleted, 'changes' => []]);

        $this->page()
            ->filterTable('kind', 'deleted')
            ->assertCanSeeTableRecords([$deleted])
            ->assertCanNotSeeTableRecords([$changed]);
    }

    public function test_it_filters_by_date_range(): void
    {
        $early = ChangeModel::factory()->create(['occurred_at' => '2026-01-05 10:00:00']);
        $mid = ChangeModel::factory()->create(['occurred_at' => '2026-02-10 23:30:00']);
        $late = ChangeModel::factory()->create(['occurred_at' => '2026-03-20 08:00:00']);

        $this->page()
            ->filterTable('period', ['from' => '2026-02-01', 'until' => '2026-02-10'])
            ->assertCanSeeTableRecords([$mid])
            ->assertCanNotSeeTableRecords([$early, $late]);
    }

    public function test_a_row_opens_the_details(): void
    {
        $entry = ChangeModel::factory()->create([
            'changes' => ['name' => ['old' => 'Stare', 'new' => 'Nowe']],
            'occurred_at' => '2026-03-04 10:30:00',
        ]);

        $this->page()
            ->mountTableAction('details', $entry)
            ->assertMountedActionModalSee(['04.03.2026 10:30', 'Zmieniono', 'Stare', 'Nowe']);
    }

    public function test_it_links_to_an_existing_record(): void
    {
        $user = $this->subject();
        $entry = ChangeModel::factory()->forRecord('users.user', $user->id)->create();

        $page = $this->page();
        $page->assertSee('Anna Nowak');

        $this->assertSame(route('filament.admin.resources.users.view', $user), $this->recordUrl($page, $entry));
    }

    public function test_a_removed_record_has_no_link_and_shows_its_last_title(): void
    {
        $entry = ChangeModel::factory()->forRecord('users.user', 987654)->deleted('Dawny Użytkownik')->create();

        $page = $this->page();
        $page->assertSee('Dawny Użytkownik');

        $this->assertNull($this->recordUrl($page, $entry));
    }

    public function test_it_is_read_only(): void
    {
        $entry = ChangeModel::factory()->create();

        $this->page()
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete')
            ->assertTableActionExists('details', record: $entry)
            ->assertTableBulkActionDoesNotExist('delete');
    }

    public function test_access_is_refused_without_the_permission(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());

        $this->assertFalse(AuditLog::canAccess());
        $this->assertNotContains('Dziennik audytu', $this->navigationLabels());
        Livewire::test(AuditLog::class)->assertForbidden();
    }

    public function test_an_extra_permission_grants_access(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create(['extra_permissions' => ['audit.audit_log']]));

        $this->assertTrue(AuditLog::canAccess());
        $this->assertContains('Dziennik audytu', $this->navigationLabels());
    }

    public function test_the_super_admin_can_open_the_page(): void
    {
        $this->page()->assertOk();
    }
}
