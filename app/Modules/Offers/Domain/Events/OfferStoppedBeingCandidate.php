<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** §10, RULE-12: an Offer stopped being a candidate without being sold: rejected, sent back to Pending, withdrawn or moved to the Trash. Active to Reserved keeps it a candidate, so it is not announced here. Carries the Offer id only; a consumer reads the offer summary for adverts. */
final readonly class OfferStoppedBeingCandidate
{
    use Dispatchable;

    public function __construct(public int $offerId) {}
}
