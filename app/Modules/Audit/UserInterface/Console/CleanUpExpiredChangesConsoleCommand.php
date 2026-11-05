<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Console;

use App\Core\Domain\Actor;
use App\Modules\Audit\Application\Commands\CleanUpExpiredChanges\CleanUpExpiredChangesCommand;
use Ecotone\Modelling\CommandBus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * WF-03, scheduled nightly. Removes expired Change entries (FR-20) and checks
 * that the daily log driver still prunes files after 14 days (FR-21). A
 * failure is logged as an error and the next night's run tries again.
 */
class CleanUpExpiredChangesConsoleCommand extends Command
{
    private const int LOG_FILE_DAYS = 14;

    protected $signature = 'audit:cleanup-retention';

    protected $description = 'Remove Change entries older than the retention period and verify the daily log retention';

    public function handle(CommandBus $bus): int
    {
        try {
            $removed = $bus->send(new CleanUpExpiredChangesCommand(Actor::system('audit::actors.retention_cleanup')));
            $this->verifyDailyLogRetention();
        } catch (Throwable $exception) {
            $this->logFailure($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Removed {$removed} expired Change entries.");

        return self::SUCCESS;
    }

    /**
     * Laravel's daily driver deletes old files itself through `days`; this only
     * proves the option is set and not above the fixed 14 days.
     */
    private function verifyDailyLogRetention(): void
    {
        $days = config('logging.channels.daily.days');

        if (! is_numeric($days) || (int) $days < 1 || (int) $days > self::LOG_FILE_DAYS) {
            throw new RuntimeException('The daily log driver must keep files for 1 to '.self::LOG_FILE_DAYS.' days, found '.var_export($days, true).'.');
        }
    }

    /** Seam for ticket 08: the aplikacja channel does not exist until then, so the default channel is used. */
    private function logFailure(Throwable $exception): void
    {
        $context = ['exception' => $exception];

        if (config('logging.channels.aplikacja') !== null) {
            Log::channel('aplikacja')->error('Audit retention cleanup failed.', $context);

            return;
        }

        Log::error('Audit retention cleanup failed.', $context);
    }
}
