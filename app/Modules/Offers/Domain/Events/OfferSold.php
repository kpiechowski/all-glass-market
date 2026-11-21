<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** §10, RULE-12: a candidate Offer was sold (from Active or from Reserved); adverts end. Carries the Offer id only; a consumer reads the offer summary for adverts. */
final readonly class OfferSold
{
    use Dispatchable;

    public function __construct(public int $offerId) {}
}
