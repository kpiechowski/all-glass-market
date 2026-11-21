<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ReleaseReservation;

use App\Core\Domain\Actor;

/** FR-17: an admin, or the owner unless an admin made it, releases the Offer's Reservation. */
final readonly class ReleaseReservationCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
    ) {}
}
