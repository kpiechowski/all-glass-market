<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Console;

use App\Core\Domain\Actor;
use App\Modules\Audit\Application\Commands\CleanUpExpiredChanges\CleanUpExpiredChangesCommand;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\CommandBus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * WF-03, scheduled nightly. Removes expired Change entries (FR-20) and checks
 * that the daily log driver still prunes files after 14 days and deletes older files itself (FR-21). A
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
            $this->deleteExpiredLogFiles();
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
     * proves the option is set on every daily channel and not above the fixed 14 days.
     */
    private function verifyDailyLogRetention(): void
    {
        foreach (config('logging.channels', []) as $name => $channel) {
            if (($channel['driver'] ?? null) !== 'daily') {
                continue;
            }

            $days = $channel['days'] ?? null;

            if (! is_numeric($days) || (int) $days < 1 || (int) $days > self::LOG_FILE_DAYS) {
                throw new RuntimeException("The daily log channel {$name} must keep files for 1 to ".self::LOG_FILE_DAYS.' days, found '.var_export($days, true).'.');
            }
        }
    }

    /**
     * Monolog prunes only when a channel writes a new file, so a rarely written
     * channel keeps files for months. Deletes `<channel>-YYYY-MM-DD.log` files
     * with a real date strictly older than 14 days, in each labelled channel's
     * own directory. Nothing else is touched, and symlinks are never followed.
     */
    private function deleteExpiredLogFiles(): void
    {
        $cutoff = now()->startOfDay()->subDays(self::LOG_FILE_DAYS);

        foreach (array_keys(config('logging.labels', [])) as $channel) {
            $path = config("logging.channels.{$channel}.path");

            if (! is_string($path) || $path === '') {
                continue;
            }

            $directory = realpath(dirname($path));
            $prefix = pathinfo($path, PATHINFO_FILENAME);

            if ($directory === false) {
                continue;
            }

            foreach (scandir($directory) ?: [] as $file) {
                if (preg_match('/^'.preg_quote($prefix, '/').'-(\d{4}-\d{2}-\d{2})\.log$/', $file, $match) !== 1) {
                    continue;
                }

                $date = CarbonImmutable::createFromFormat('!Y-m-d', $match[1]);

                if ($date === false || $date->format('Y-m-d') !== $match[1] || ! $date->lt($cutoff)) {
                    continue;
                }

                $file = $directory.DIRECTORY_SEPARATOR.$file;

                if (is_link($file) || ! is_file($file)) {
                    continue;
                }

                unlink($file);
            }
        }
    }

    private function logFailure(Throwable $exception): void
    {
        Log::channel('aplikacja')->error('Audit retention cleanup failed.', ['exception' => $exception]);
    }
}
