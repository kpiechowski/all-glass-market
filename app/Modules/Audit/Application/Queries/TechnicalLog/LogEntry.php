<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Queries\TechnicalLog;

/** One entry of a daily file: the header line plus the lines that follow it (FR-16). */
final readonly class LogEntry
{
    public function __construct(
        public string $key,
        public string $time,
        public string $level,
        public string $message,
        public string $context,
        public string $stackTrace,
    ) {}

    /** The message cut to its first line, for the table. */
    public function summary(): string
    {
        return strtok($this->message, "\n") ?: '';
    }

    /** The searchable text: message and context, stack trace included. */
    public function text(): string
    {
        return $this->message."\n".$this->context."\n".$this->stackTrace;
    }
}
