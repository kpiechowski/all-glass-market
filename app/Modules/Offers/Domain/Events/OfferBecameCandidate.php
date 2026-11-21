<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** §10, RULE-12: an Offer became a candidate for adverts: approved, resumed, returned from a sale, released from a Reservation or restored from the Trash into Active or Reserved. Carries the Offer id only; a consumer reads the offer summary for adverts. */
final readonly class OfferBecameCandidate
{
    use Dispatchable;

    public function __construct(public int $offerId) {}
}
