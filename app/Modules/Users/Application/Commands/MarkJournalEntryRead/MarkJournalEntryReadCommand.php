<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\MarkJournalEntryRead;

use App\Core\Domain\Actor;

final readonly class MarkJournalEntryReadCommand
{
    public function __construct(
        public Actor $actor,
        public int $entryId,
    ) {}
}
