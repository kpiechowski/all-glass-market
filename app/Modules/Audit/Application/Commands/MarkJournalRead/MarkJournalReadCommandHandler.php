<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Commands\MarkJournalRead;

use App\Modules\Audit\Domain\Exceptions\JournalOwnershipException;
use App\Modules\Audit\Domain\Models\UserJournal;
use App\Modules\Audit\Domain\Repositories\UserJournalRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

class MarkJournalReadCommandHandler
{
    public function __construct(
        private UserJournalRepository $userJournalRepository,
    ) {}

    #[CommandHandler]
    public function handle(MarkJournalReadCommand $command): void
    {
        /** @var UserJournal $journal */
        $journal = $this->userJournalRepository->findOrFail($command->journalId);

        if ($journal->user_id !== $command->userId) {
            throw new JournalOwnershipException;
        }

        $this->userJournalRepository->markRead($command->journalId);
    }
}
