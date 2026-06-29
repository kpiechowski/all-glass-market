<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Commands\MarkJournalRead;

readonly class MarkJournalReadCommand
{
    public function __construct(
        public int $journalId,
        public int $userId,
    ) {}
}
