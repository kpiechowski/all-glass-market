<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * FR-28: the buyer's price of an Offer changed because its owner's Account type or the VAT rate
 * changed. Raised for every Offer of the affected owners that has an entered price, in any status;
 * adverts follow it for Offers that are candidates (consumed in ticket 13).
 */
final readonly class OfferBuyersPriceChanged
{
    use Dispatchable;

    public function __construct(
        public int $offerId,
        public int $previousBuyersPriceGrosze,
        public int $buyersPriceGrosze,
        public CarbonImmutable $changedAt,
    ) {}
}
