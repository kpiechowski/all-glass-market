<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** §10, RULE-12: a candidate Offer was reserved (also by accepting an Inquiry). Carries the Offer id only; a consumer reads the offer summary for adverts. */
final readonly class OfferReserved
{
    use Dispatchable;

    public function __construct(public int $offerId) {}
}
