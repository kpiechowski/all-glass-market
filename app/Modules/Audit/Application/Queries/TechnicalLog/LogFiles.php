<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Queries\TechnicalLog;

use App\Modules\Audit\Domain\Exceptions\InvalidLogSelection;

/**
 * Where a channel's file for a day lives (FR-16). The only way the Technical log
 * page and its download reach a file, so a path outside the log directory cannot
 * be built from client input:
 *
 * - the channel must be a key of `config('logging.labels')`, and its directory
 *   comes from that channel's configured path, never from the request;
 * - the day must be a real date written exactly `Y-m-d`;
 * - the name is `<channel>-<day>.log`, built here from those two checked parts;
 * - an existing file is served only when it really sits in that directory
 *   (a symlink pointing elsewhere is refused).
 */
final class LogFiles
{
    /** @return array<string, string> channel key => translated label */
    public function channels(): array
    {
        return array_map(fn (string $key): string => (string) __($key), config('logging.labels', []));
    }

    public function defaultChannel(): string
    {
        return array_key_first(config('logging.labels', [])) ?? 'aplikacja';
    }

    /**
     * @throws InvalidLogSelection
     */
    public function pathFor(string $channel, string $day): string
    {
        $labels = config('logging.labels', []);

        if (! array_key_exists($channel, $labels) || ! is_string(config("logging.channels.{$channel}.path"))) {
            throw InvalidLogSelection::unknownChannel();
        }

        if (! $this->isRealDay($day)) {
            throw InvalidLogSelection::invalidDay();
        }

        return dirname(config("logging.channels.{$channel}.path")).DIRECTORY_SEPARATOR."{$channel}-{$day}.log";
    }

    /**
     * The file when the selection is valid, the file exists and it sits in the
     * channel's directory; null in every other case.
     */
    public function existingFor(string $channel, string $day): ?string
    {
        try {
            $path = $this->pathFor($channel, $day);
        } catch (InvalidLogSelection) {
            return null;
        }

        if (! is_file($path)) {
            return null;
        }

        $real = realpath($path);
        $directory = realpath(dirname($path));

        return $real !== false && $directory !== false && dirname($real) === $directory ? $real : null;
    }

    private function isRealDay(string $day): bool
    {
        return preg_match('/\A(\d{4})-(\d{2})-(\d{2})\z/', $day, $parts) === 1
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }
}
