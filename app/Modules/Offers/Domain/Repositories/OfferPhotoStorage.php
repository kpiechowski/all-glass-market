<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Repositories;

use App\Modules\Offers\Domain\ValueObjects\OfferPhotos;

/** The files behind an Offer's two photo lists (FR-05), removed when the Offer is deleted permanently (FR-26). */
interface OfferPhotoStorage
{
    /** Deletes every public and private file of the lists; a file already gone is not an error. */
    public function deleteAll(OfferPhotos $photos): void;
}
