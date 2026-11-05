<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\UserInterface;

use App\Modules\Audit\UserInterface\Filament\Pages\TechnicalLog\TechnicalLog;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class TechnicalLogTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        /* The custom-data table has no query, so Filament's own count assertion cannot see it. */
        Testable::macro('assertTableTotal', function (int $expected): Testable {
            Assert::assertSame($expected, $this->instance()->getTableRecords()->total());

            return $this;
        });
        $this->directory = sys_get_temp_dir().'/technical-log-'.uniqid();
        File::ensureDirectoryExists($this->directory);

        /* The app's own log lines must not land in the files under test. */
        config(['logging.default' => 'null', 'logging.deprecations.channel' => 'null']);

        foreach (array_keys(config('logging.labels')) as $channel) {
            config(["logging.channels.{$channel}.path" => "{$this->directory}/{$channel}.log"]);
        }
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    private function write(string $channel, string $day, string $content): void
    {
        file_put_contents("{$this->directory}/{$channel}-{$day}.log", $content);
    }

    private function page(): Testable
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        return Livewire::test(TechnicalLog::class);
    }

    private function sampleDay(): void
    {
        $this->write('aplikacja', now()->format('Y-m-d'), implode('', [
            "[2026-11-05 09:00:00] local.INFO: Order imported {\"order\":\"A-77\"}\n",
            "[2026-11-05 09:30:00] local.ERROR: Sync failed {\"exception\":\"[object] (RuntimeException(code: 0): Sync failed at /app/S.php:9)\n[stacktrace]\n#0 /app/T.php(4): S->run()\n#1 {main}\n\"}\n",
            "[2026-11-05 10:00:00] local.WARNING: Slow request\nsecond line of the message\n",
        ]));
    }

    public function test_it_opens_on_aplikacja_today(): void
    {
        $this->sampleDay();

        $this->page()
            ->assertSet('data.channel', 'aplikacja')
            ->assertSet('data.day', now()->format('Y-m-d'))
            ->assertTableTotal(3)
            ->assertSee('Sync failed');
    }

    public function test_it_lists_newest_first_with_a_one_line_message(): void
    {
        $this->sampleDay();

        $this->page()
            ->assertSeeInOrder(['Slow request', 'Sync failed', 'Order imported'])
            ->assertDontSee('second line of the message');
    }

    public function test_it_switches_channel_and_day(): void
    {
        $this->sampleDay();
        $this->write('bezpieczenstwo', '2026-01-02', "[2026-01-02 08:00:00] local.INFO: Zalogowano\n");

        $this->page()
            ->set('data.channel', 'bezpieczenstwo')
            ->assertTableTotal(0)
            ->set('data.day', '2026-01-02')
            ->assertTableTotal(1)
            ->assertSee('Zalogowano');
    }

    public function test_it_filters_by_level(): void
    {
        $this->sampleDay();

        $this->page()
            ->filterTable('level', 'error')
            ->assertTableTotal(1)
            ->assertSee('Sync failed')
            ->assertDontSee('Order imported');
    }

    public function test_it_searches_message_and_context(): void
    {
        $this->sampleDay();

        $this->page()
            ->searchTable('A-77')
            ->assertTableTotal(1)
            ->assertSee('Order imported')
            ->searchTable('slow')
            ->assertTableTotal(1)
            ->assertSee('Slow request');
    }

    public function test_an_entry_opens_with_message_context_and_stack_trace(): void
    {
        $this->sampleDay();
        $page = $this->page();
        $key = (string) array_key_first($page->instance()->getTableRecords()->all());

        $page->filterTable('level', 'error');
        $key = (string) array_key_first($page->instance()->getTableRecords()->all());

        $page->mountTableAction('details', $key)
            ->assertMountedActionModalSee(['Sync failed', 'RuntimeException', 'Ślad stosu', '#0 /app/T.php(4): S->run()']);
    }

    public function test_a_multi_line_message_shows_in_full_in_the_details(): void
    {
        $this->sampleDay();
        $page = $this->page()->filterTable('level', 'warning');
        $key = (string) array_key_first($page->instance()->getTableRecords()->all());

        $page->mountTableAction('details', $key)->assertMountedActionModalSee(['second line of the message']);
    }

    public function test_a_day_without_a_file_shows_the_empty_state(): void
    {
        $this->page()
            ->assertTableTotal(0)
            ->assertSee('Brak wpisów')
            ->assertActionHidden('download');
    }

    public function test_an_empty_file_shows_the_empty_state(): void
    {
        $this->write('aplikacja', now()->format('Y-m-d'), '');

        $this->page()->assertTableTotal(0)->assertSee('Brak wpisów');
    }

    public function test_it_downloads_the_days_file(): void
    {
        $this->sampleDay();

        $this->page()
            ->callAction('download')
            ->assertFileDownloaded('aplikacja-'.now()->format('Y-m-d').'.log');
    }

    public function test_a_tampered_channel_or_day_reads_and_serves_nothing(): void
    {
        $this->sampleDay();
        file_put_contents("{$this->directory}/passwd", "[2026-11-05 09:00:00] local.INFO: leaked\n");

        $page = $this->page()
            ->set('data.channel', '../passwd')
            ->assertTableTotal(0)
            ->assertDontSee('leaked')
            ->assertActionHidden('download');

        $page->assertOk();
    }

    public function test_it_cannot_delete_anything(): void
    {
        $this->sampleDay();

        $this->page()
            ->assertTableActionDoesNotExist('delete')
            ->assertTableBulkActionDoesNotExist('delete')
            ->assertActionDoesNotExist('delete');
    }

    public function test_access_is_refused_without_the_permission(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());

        $this->assertFalse(TechnicalLog::canAccess());
        Livewire::test(TechnicalLog::class)->assertForbidden();
    }

    public function test_an_extra_permission_grants_access(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create(['extra_permissions' => ['audit.technical_logs']]));

        $this->assertTrue(TechnicalLog::canAccess());
    }
}
