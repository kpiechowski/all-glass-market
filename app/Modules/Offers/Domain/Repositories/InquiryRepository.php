<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Repositories;

use App\Core\Contracts\Repository;
use App\Modules\Offers\Domain\Entities\Inquiry;

/** @extends Repository<Inquiry> */
interface InquiryRepository extends Repository
{
    /**
     * FR-23: the Offer's Inquiries still Sent or Read.
     *
     * @return list<Inquiry>
     */
    public function openOfOffer(int $offerId): array;

    /** FR-26: removes every Inquiry of the Offer, closed ones included. */
    public function removeAllOfOffer(int $offerId): void;
}
