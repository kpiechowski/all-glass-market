<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ResumeOffer;

use App\Core\Domain\Actor;

/** FR-24: "Wznów sprzedaż" returns a Withdrawn Offer to Active when it may skip approval. */
final readonly class ResumeOfferCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
    ) {}
}
