<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Repositories;

use App\Core\Contracts\Repository;
use App\Modules\Offers\Domain\Entities\Offer;

/** @extends Repository<Offer> */
interface OfferRepository extends Repository
{
    /** RULE-13: whether an Offer already has the number. */
    public function numberIsTaken(string $number): bool;
}
