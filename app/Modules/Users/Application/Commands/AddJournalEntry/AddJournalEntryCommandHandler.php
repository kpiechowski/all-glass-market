<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\AddJournalEntry;

use App\Modules\Users\Domain\Entities\JournalEntry;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Domain\Events\JournalEntryAdded;
use App\Modules\Users\Domain\Repositories\JournalEntryRepository;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-29. Every module may write, so the actor is recorded but needs no
 * Permission. An unknown User gets no entry and no error: the writing module's
 * own operation must still complete. A Deactivated User still gets entries (D-47).
 */
final class AddJournalEntryCommandHandler
{
    public function __construct(
        private UserRepository $users,
        private JournalEntryRepository $entries,
    ) {}

    /** @return int|null the new entry's id, null when the User does not exist */
    #[CommandHandler]
    public function handle(AddJournalEntryCommand $command): ?int
    {
        $severity = JournalSeverity::from($command->severity);

        $entry = JournalEntry::write(
            $command->userId, $command->kind, $severity, $command->title, $command->content, $command->subjectId,
        );

        if (! $this->users->exists($command->userId)) {
            return null;
        }

        $id = $this->entries->add($entry);

        event(new JournalEntryAdded($id, $command->userId));

        return $id;
    }
}
