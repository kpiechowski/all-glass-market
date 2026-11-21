<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\SellOffer;

use App\Core\Domain\Actor;

/** FR-18: an admin or the owner (not over an admin's Reservation) sells an Active or Reserved Offer; the price is in grosze. */
final readonly class SellOfferCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
        public string $channel,
        public int $salePriceGrosze,
    ) {}
}
