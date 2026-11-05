<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Queries\TechnicalLog;

/** One page of matching entries, and how many entries match in all. */
final readonly class LogEntryPage
{
    /** @param list<LogEntry> $entries */
    public function __construct(public array $entries, public int $total) {}
}
