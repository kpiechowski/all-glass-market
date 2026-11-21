<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\WithdrawOffer;

use App\Core\Domain\Actor;

/** FR-24: the owner or an admin withdraws a Pending, Rejected or Active Offer. */
final readonly class WithdrawOfferCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
    ) {}
}
