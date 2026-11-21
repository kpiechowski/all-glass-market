<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\AnnounceBuyersPriceChanges;

use App\Core\Domain\Actor;

/** FR-28: an owner's Account type changed; announce the Offers whose buyer's price moved with it. Types are 'private' or 'company'. */
final readonly class AnnounceBuyersPriceChangesCommand
{
    public function __construct(
        public Actor $actor,
        public int $ownerId,
        public string $previousAccountType,
        public string $accountType,
    ) {}
}
