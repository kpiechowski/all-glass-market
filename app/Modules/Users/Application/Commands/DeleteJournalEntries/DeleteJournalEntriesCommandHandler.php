<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\DeleteJournalEntries;

use App\Modules\Users\Domain\Exceptions\JournalRefused;
use App\Modules\Users\Domain\Repositories\JournalEntryRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-31: an owner deletes one or many of their own entries; nobody deletes
 * another User's. One foreign id refuses the whole command, and the bus
 * transaction leaves the others untouched.
 */
final class DeleteJournalEntriesCommandHandler
{
    public function __construct(private JournalEntryRepository $entries) {}

    #[CommandHandler]
    public function handle(DeleteJournalEntriesCommand $command): void
    {
        $ownerId = $command->actor->id ?? throw JournalRefused::notOwner();

        foreach (array_unique($command->entryIds) as $entryId) {
            $entry = $this->entries->find($entryId) ?? throw JournalRefused::entryNotFound();
            $entry->ensureOwnedBy($ownerId);

            $this->entries->remove($entry);
        }
    }
}
