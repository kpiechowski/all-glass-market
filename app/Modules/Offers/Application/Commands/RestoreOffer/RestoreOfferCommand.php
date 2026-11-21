<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\RestoreOffer;

use App\Core\Domain\Actor;

/** FR-25: an admin restores an Offer from the Trash. */
final readonly class RestoreOfferCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
    ) {}
}
