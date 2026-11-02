<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\DeleteJournalEntries;

use App\Core\Contracts\PanelNotifier;
use App\Modules\Users\Application\ModuleApi\JournalEntryUrls;
use App\Modules\Users\Domain\Exceptions\JournalRefused;
use App\Modules\Users\Domain\Repositories\JournalEntryRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-31: an owner deletes one or many of their own entries; nobody deletes
 * another User's. An entry's panel notification goes with it (FR-32). One foreign id refuses the whole command, and the bus
 * transaction leaves the others untouched.
 */
final class DeleteJournalEntriesCommandHandler
{
    public function __construct(private JournalEntryRepository $entries,
        private PanelNotifier $notifier,
        private JournalEntryUrls $urls,
    ) {}

    #[CommandHandler]
    public function handle(DeleteJournalEntriesCommand $command): void
    {
        $ownerId = $command->actor->id ?? throw JournalRefused::notOwner();

        foreach (array_unique($command->entryIds) as $entryId) {
            $entry = $this->entries->find($entryId) ?? throw JournalRefused::entryNotFound();
            $entry->ensureOwnedBy($ownerId);

            $this->entries->remove($entry);
            $this->notifier->retract($this->urls->show($entryId));
        }
    }
}
