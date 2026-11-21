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

    /** §10: whether the Offer is Active or Reserved and outside the Trash. */
    public function isCandidate(int $offerId): bool;

    /**
     * §10: the candidates (Active or Reserved, outside the Trash) that have the Glass model, in one read.
     *
     * @return list<int>
     */
    public function candidateIdsOfGlassModel(int $glassModelId): array;

    /**
     * §10: the candidates that have the producer, in one read.
     *
     * @return list<int>
     */
    public function candidateIdsOfProducer(int $producerId): array;
}
