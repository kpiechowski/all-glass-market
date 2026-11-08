<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\UserInterface;

use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Audit\UserInterface\Filament\Pages\HistoryAndLogs\HistoryAndLogs;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/** D-24a: one page, three page tabs, each behind its own Permission. */
class HistoryAndLogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        config(['logging.default' => 'null', 'logging.deprecations.channel' => 'null']);
    }

    /** @param list<string> $extraPermissions */
    private function actingWith(array $extraPermissions): void
    {
        $this->actingAs(UserModel::factory()->admin()->create(['extra_permissions' => $extraPermissions]));
    }

    private function page(?string $tab = null): Testable
    {
        return Livewire::withQueryParams($tab === null ? [] : ['zakladka' => $tab])->test(HistoryAndLogs::class);
    }

    public function test_the_super_admin_sees_all_three_tabs_and_opens_on_the_history(): void
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        $this->page()
            ->assertSet('activeTab', 'historia')
            ->assertSeeInOrder(['Historia zmian', 'Logi techniczne', 'Nieudane zadania'])
            ->assertSee('zakladka=zadania', false);
    }

    public function test_the_audit_log_permission_alone_shows_only_the_history_tab(): void
    {
        $this->actingWith(['audit.audit_log']);

        $this->page()
            ->assertSet('activeTab', 'historia')
            ->assertSee('Historia zmian')
            ->assertDontSee('Logi techniczne')
            ->assertDontSee('Nieudane zadania');
    }

    public function test_the_technical_logs_permission_alone_opens_on_the_logs_and_hides_the_history(): void
    {
        $this->actingWith(['audit.technical_logs']);

        $this->page()
            ->assertSet('activeTab', 'logi')
            ->assertSee(['Logi techniczne', 'Nieudane zadania'])
            ->assertDontSee('zakladka=historia', false);
    }

    public function test_a_forbidden_tab_in_the_url_opens_the_first_allowed_one(): void
    {
        $entry = ChangeModel::factory()->create();
        $this->actingWith(['audit.audit_log']);

        $this->page('zadania')
            ->assertSet('activeTab', 'historia')
            ->assertCanSeeTableRecords([$entry]);
    }

    public function test_an_unknown_tab_in_the_url_opens_the_first_allowed_one(): void
    {
        $this->actingWith(['audit.technical_logs']);

        $this->page('cokolwiek')->assertSet('activeTab', 'logi');
    }

    public function test_the_tab_cannot_be_switched_from_the_client(): void
    {
        $this->actingWith(['audit.audit_log']);

        $this->expectException(CannotUpdateLockedPropertyException::class);

        $this->page()->set('activeTab', 'zadania');
    }

    public function test_each_tab_renders_its_own_table(): void
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        $this->page('historia')->assertTableColumnExists('record')->assertTableColumnExists('actor')->assertTableColumnDoesNotExist('summary');
        $this->page('logi')->assertTableColumnExists('summary')->assertTableFilterExists('channel')->assertTableColumnDoesNotExist('record');
        $this->page('zadania')->assertTableColumnExists('error')->assertTableColumnDoesNotExist('summary');
    }

    public function test_the_download_shows_only_on_the_logs_tab(): void
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        $this->page('historia')->assertActionHidden('download');
        $this->page('zadania')->assertActionHidden('download');
    }

    public function test_without_either_permission_the_page_is_closed(): void
    {
        $this->actingWith([]);

        $this->assertFalse(HistoryAndLogs::canAccess());
        $this->page('historia')->assertForbidden();
    }
}
