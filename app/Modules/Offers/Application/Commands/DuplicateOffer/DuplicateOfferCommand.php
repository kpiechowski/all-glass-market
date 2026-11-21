<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\DuplicateOffer;

use App\Core\Domain\Actor;

/** FR-27, RULE-07: the owner or an admin copies an Offer into a new Draft owned by the actor; the handler returns the new id. */
final readonly class DuplicateOfferCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
    ) {}
}
