<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\UpdateOffer;

use App\Core\Domain\Actor;

/**
 * FR-01 to FR-06: saves the Offer as a Draft. The condition is the case value of `OfferCondition`; the price is in grosze.
 * The photos are storage paths in their order (FR-05); a null list leaves that kind as it is.
 *
 * @param  list<string>|null  $publicPhotos
 * @param  list<string>|null  $privatePhotos
 */
final readonly class UpdateOfferCommand
{
    public function __construct(
        public Actor $actor,
        public int $offerId,
        public ?int $glassModelId = null,
        public ?string $identificationHint = null,
        public ?int $producerId = null,
        public ?int $year = null,
        public ?string $condition = null,
        public ?int $rating = null,
        public ?string $description = null,
        public ?string $privateNotes = null,
        public ?int $priceGrosze = null,
        public ?array $publicPhotos = null,
        public ?array $privatePhotos = null,
    ) {}
}
