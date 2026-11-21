<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\DeleteOfferPermanently;

use App\Core\Domain\Actor;

/** FR-26: an admin deletes an Offer in the Trash for good. */
final readonly class DeleteOfferPermanentlyCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
    ) {}
}
