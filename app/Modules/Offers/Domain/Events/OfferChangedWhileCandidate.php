<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** §10, RULE-12: an Active or Reserved Offer outside the Trash changed what adverts show (its content, price, Glass model or public photos, a buyer's price change, or a change in Catalogue). Carries the Offer id only; a consumer reads the offer summary for adverts. */
final readonly class OfferChangedWhileCandidate
{
    use Dispatchable;

    public function __construct(public int $offerId) {}
}
