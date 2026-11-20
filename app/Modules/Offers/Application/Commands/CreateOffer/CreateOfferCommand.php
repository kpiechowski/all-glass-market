<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\CreateOffer;

use App\Core\Domain\Actor;

/** FR-01 to FR-06: saves the Offer as a Draft. The condition is the case value of `OfferCondition`; the price is in grosze. */
final readonly class CreateOfferCommand
{
    public function __construct(
        public Actor $actor,
        public ?int $glassModelId = null,
        public ?string $identificationHint = null,
        public ?int $producerId = null,
        public ?int $year = null,
        public ?string $condition = null,
        public ?int $rating = null,
        public ?string $description = null,
        public ?string $privateNotes = null,
        public ?int $priceGrosze = null,
    ) {}
}
