<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class JournalEntryAdded
{
    use Dispatchable;

    public function __construct(
        public int $entryId,
        public int $ownerId,
    ) {}
}
