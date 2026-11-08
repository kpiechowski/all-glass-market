<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Queries\TechnicalLog;

use App\Modules\Audit\Domain\Enums\LogLevel;
use Generator;

/**
 * Reads a daily log file newest first without loading it (FR-16).
 *
 * The file is read backwards in 64 KB chunks and handed out one entry at a time
 * (a generator), so memory holds one chunk plus one entry, never the file. One
 * pass applies the filters, counts every match for the pager and keeps only the
 * requested page's entries. An entry starts at a `[YYYY-MM-DD HH:MM:SS] env.LEVEL:`
 * line; every line after it, such as a stack trace, belongs to it.
 *
 * The cost is one pass over the file per page view; an entry larger than memory
 * is not expected, since the largest unit held is a single entry.
 */
final class ReadLogEntries
{
    private const int CHUNK_SIZE = 65536;

    private const string HEADER = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})(?:\.\d+)?(?:[+-]\d{2}:?\d{2}|Z)?\] [^\s.]+\.([A-Za-z]+): (.*)$/s';

    public function page(string $path, ?LogLevel $level, ?string $search, int $page, int $perPage): LogEntryPage
    {
        $first = max(0, ($page - 1) * $perPage);
        $total = 0;
        $kept = [];

        foreach ($this->entries($path) as $entry) {
            if (! $this->matches($entry, $level, $search)) {
                continue;
            }

            if ($total >= $first && count($kept) < $perPage) {
                $kept[] = $entry;
            }

            $total++;
        }

        return new LogEntryPage($kept, $total);
    }

    /** The entry that starts at the given byte offset (its key), or null. */
    public function find(string $path, string $key): ?LogEntry
    {
        if (! ctype_digit($key)) {
            return null;
        }

        foreach ($this->entries($path) as $entry) {
            if ($entry->key === $key) {
                return $entry;
            }
        }

        return null;
    }

    /** @return Generator<int, LogEntry> */
    public function entries(string $path): Generator
    {
        $continuation = [];

        foreach ($this->linesBackwards($path) as [$offset, $line]) {
            if (preg_match(self::HEADER, $line, $header) !== 1) {
                $continuation[] = $line;

                continue;
            }

            yield $this->entry((string) $offset, $header, array_reverse($continuation));
            $continuation = [];
        }
    }

    private function matches(LogEntry $entry, ?LogLevel $level, ?string $search): bool
    {
        if ($level !== null && $entry->level !== $level->value) {
            return false;
        }

        return blank($search) || mb_stripos($entry->text(), $search) !== false;
    }

    /**
     * @param  array<int, string>  $header
     * @param  list<string>  $continuation
     */
    private function entry(string $key, array $header, array $continuation): LogEntry
    {
        $body = rtrim(implode("\n", [$header[3], ...$continuation]));
        $position = strpos($body, ' {"');
        $message = $position === false ? $body : substr($body, 0, $position);
        $context = $position === false ? '' : substr($body, $position + 1);
        $context = preg_replace('/ (\[\]|\{\})$/', '', $context) ?? $context;
        $trace = '';

        $marker = strpos($context, "\n[stacktrace]\n");

        if ($marker !== false) {
            $trace = preg_replace('/"\}\s*$/', '', substr($context, $marker + 1)) ?? '';
            $context = substr($context, 0, $marker);
        }

        return new LogEntry($key, $header[1], strtolower($header[2]), $message, $context, rtrim($trace));
    }

    /**
     * Lines from the end of the file to the start, each with the byte offset it starts at.
     *
     * @return Generator<int, array{int, string}>
     */
    private function linesBackwards(string $path): Generator
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return;
        }

        try {
            $position = (int) filesize($path);
            $buffer = '';

            while ($position > 0) {
                $read = min(self::CHUNK_SIZE, $position);
                $position -= $read;
                fseek($handle, $position);
                $buffer = (string) fread($handle, $read).$buffer;

                $lines = explode("\n", $buffer);
                $buffer = array_shift($lines);

                $offsets = [];
                $offset = $position + strlen($buffer) + 1;

                foreach ($lines as $line) {
                    $offsets[] = $offset;
                    $offset += strlen($line) + 1;
                }

                for ($i = count($lines) - 1; $i >= 0; $i--) {
                    yield [$offsets[$i], $lines[$i]];
                }
            }

            yield [0, $buffer];
        } finally {
            fclose($handle);
        }
    }
}
