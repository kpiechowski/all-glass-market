<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Events;

use App\Core\Domain\Actor;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * FR-23: an Offer stopped being Active, by any route (reserved, sold, withdrawn, rejected, sent back to Pending,
 * moved to the Trash). The Offer notices it; the repository adapter dispatches this inside the command's
 * transaction, carrying the Actor of the command that did it.
 */
final readonly class OfferStoppedBeingActive
{
    use Dispatchable;

    public function __construct(
        public int $offerId,
        public Actor $actor,
    ) {}
}
