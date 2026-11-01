<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\DeleteJournalEntries;

use App\Core\Domain\Actor;

final readonly class DeleteJournalEntriesCommand
{
    /** @param list<int> $entryIds */
    public function __construct(
        public Actor $actor,
        public array $entryIds,
    ) {}
}
