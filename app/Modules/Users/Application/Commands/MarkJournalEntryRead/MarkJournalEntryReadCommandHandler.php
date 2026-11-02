<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\MarkJournalEntryRead;

use App\Modules\Users\Domain\Exceptions\JournalRefused;
use App\Modules\Users\Domain\Repositories\JournalEntryRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-31: only the owner marks an entry read, whether by the action, by opening
 * its details or by opening its notification. The owner is the guard (RULE-12),
 * so a system actor, which is nobody's owner, is refused.
 */
final class MarkJournalEntryReadCommandHandler
{
    public function __construct(private JournalEntryRepository $entries) {}

    #[CommandHandler]
    public function handle(MarkJournalEntryReadCommand $command): void
    {
        $entry = $this->entries->find($command->entryId) ?? throw JournalRefused::entryNotFound();

        $entry->markReadBy($command->actor->id ?? 0);

        $this->entries->save($entry);
    }
}
