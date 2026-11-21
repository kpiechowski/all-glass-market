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

    /**
     * FR-35: the ids of every Offer one owner has, in any status, the Trash included (the entity decides).
     *
     * @return list<int>
     */
    public function idsOfOwner(int $ownerId): array;

    /**
     * FR-28: the Offers of one owner that have an entered price, in any status.
     *
     * @return list<array{id: int, grosze: int}>
     */
    public function pricedOffersOfOwner(int $ownerId): array;

    /**
     * FR-28: every Offer that has an entered price, with its owner.
     *
     * @return list<array{id: int, ownerId: int, grosze: int}>
     */
    public function pricedOffers(): array;
}
