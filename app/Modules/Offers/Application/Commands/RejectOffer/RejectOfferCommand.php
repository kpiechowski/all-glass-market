<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\RejectOffer;

use App\Core\Domain\Actor;

/** FR-09: an admin rejects a Pending or Active Offer with a required reason. */
final readonly class RejectOfferCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
        public string $reason,
    ) {}
}
