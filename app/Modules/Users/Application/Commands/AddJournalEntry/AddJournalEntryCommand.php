<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\AddJournalEntry;

use App\Core\Domain\Actor;

final readonly class AddJournalEntryCommand
{
    public function __construct(
        public Actor $actor,
        public int $userId,
        public string $kind,
        public string $severity,
        public string $title,
        public string $content,
        public ?int $subjectId = null,
    ) {}
}
