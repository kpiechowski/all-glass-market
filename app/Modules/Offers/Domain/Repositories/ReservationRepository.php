<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Repositories;

use App\Core\Contracts\Repository;
use App\Modules\Offers\Domain\Entities\Reservation;

/** @extends Repository<Reservation> */
interface ReservationRepository extends Repository
{
    /** RULE-05: the Offer's one current Reservation, if any. */
    public function findCurrentForOffer(int $offerId): ?Reservation;

    /** FR-26: removes every Reservation of the Offer, ended ones included. */
    public function removeAllOfOffer(int $offerId): void;
}
