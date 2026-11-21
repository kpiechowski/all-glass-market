<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ReserveOffer;

use App\Core\Domain\Actor;

/** FR-16: an admin or the owner reserves an Active Offer with a free-text "for whom" note. */
final readonly class ReserveOfferCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
        public string $note,
    ) {}
}
