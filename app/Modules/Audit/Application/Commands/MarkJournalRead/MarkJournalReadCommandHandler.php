<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Commands\MarkJournalRead;

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
        $this->userJournalRepository->markRead($command->journalId);
    }
}
