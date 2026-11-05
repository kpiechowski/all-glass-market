<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\UserInterface\Console;

use App\Modules\Audit\Domain\Repositories\ChangeRepository;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class CleanUpExpiredChangesConsoleCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $logPath = '';

    protected function tearDown(): void
    {
        array_map(unlink(...), glob(str_replace('.log', '-*.log', $this->logPath)) ?: []);

        parent::tearDown();
    }

    public function test_it_removes_expired_entries(): void
    {
        $old = ChangeModel::factory()->bySystem()->create(['occurred_at' => now()->subDays(400)]);
        $recent = ChangeModel::factory()->bySystem()->create(['occurred_at' => now()->subDays(10)]);

        $this->artisan('audit:cleanup-retention')->assertSuccessful();

        $this->assertNull(ChangeModel::find($old->id));
        $this->assertNotNull(ChangeModel::find($recent->id));
    }

    public function test_it_runs_nightly(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event): bool => str_contains((string) $event->command, 'audit:cleanup-retention'));

        $this->assertCount(1, $events);
        $this->assertSame('0 3 * * *', $events->first()->expression);
    }

    public function test_a_failure_is_logged_as_an_error_and_fails_the_command(): void
    {
        $this->app->bind(ChangeRepository::class, fn () => throw new RuntimeException('db down'));
        $this->logToTemporaryFile();

        $this->artisan('audit:cleanup-retention')->assertFailed();

        $this->assertStringContainsString('testing.ERROR: Audit retention cleanup failed.', $this->loggedText());
    }

    public function test_a_daily_log_driver_that_keeps_files_too_long_is_logged_as_an_error(): void
    {
        config(['logging.channels.kolejka.days' => 30]);
        $this->logToTemporaryFile();

        $this->artisan('audit:cleanup-retention')->assertFailed();

        $this->assertStringContainsString('testing.ERROR: Audit retention cleanup failed.', $this->loggedText());
    }

    public function test_the_default_daily_driver_keeps_14_days(): void
    {
        $this->logToTemporaryFile();
        $this->artisan('audit:cleanup-retention')->assertSuccessful();

        $this->assertStringNotContainsString('ERROR', $this->loggedText());
    }

    private function logToTemporaryFile(): void
    {
        $this->logPath = sys_get_temp_dir().'/audit-cleanup-'.uniqid().'.log';
        config(['logging.channels.aplikacja.path' => $this->logPath]);
        Log::forgetChannel('aplikacja');
    }

    private function loggedText(): string
    {
        return implode('', array_map(file_get_contents(...), glob(str_replace('.log', '-*.log', $this->logPath)) ?: []));
    }
}
