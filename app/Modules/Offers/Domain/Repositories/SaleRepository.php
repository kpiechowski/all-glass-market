<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Repositories;

use App\Core\Contracts\Repository;
use App\Modules\Offers\Domain\Entities\Sale;

/** @extends Repository<Sale> */
interface SaleRepository extends Repository
{
    /** FR-19: the Offer's latest Sale that was not returned, the one a return applies to. */
    public function findCurrentForOffer(int $offerId): ?Sale;

    /** FR-26: removes every Sale of the Offer, returned ones included. */
    public function removeAllOfOffer(int $offerId): void;
}
