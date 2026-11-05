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
        Log::spy();

        $this->artisan('audit:cleanup-retention')->assertFailed();

        Log::shouldHaveReceived('error')->once();
    }

    public function test_a_daily_log_driver_that_keeps_files_too_long_is_logged_as_an_error(): void
    {
        config(['logging.channels.daily.days' => 0]);
        Log::spy();

        $this->artisan('audit:cleanup-retention')->assertFailed();

        Log::shouldHaveReceived('error')->once();
    }

    public function test_the_default_daily_driver_keeps_14_days(): void
    {
        Log::spy();

        $this->artisan('audit:cleanup-retention')->assertSuccessful();

        Log::shouldNotHaveReceived('error');
    }
}
