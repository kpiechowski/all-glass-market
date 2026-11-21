<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\OutdateInquiriesOfOffer;

use App\Core\Domain\Actor;

/** FR-23: the Offer stopped being Active; the Actor is the one whose command did it. Sent by the reaction only. */
final readonly class OutdateInquiriesOfOfferCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
    ) {}
}
