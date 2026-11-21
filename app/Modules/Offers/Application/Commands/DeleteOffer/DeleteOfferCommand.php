<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\DeleteOffer;

use App\Core\Domain\Actor;

/** FR-25: an admin deletes an Offer into the Trash. */
final readonly class DeleteOfferCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
    ) {}
}
