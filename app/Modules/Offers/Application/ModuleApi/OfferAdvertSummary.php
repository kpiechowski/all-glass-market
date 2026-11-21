<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\ModuleApi;

use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummary;

/**
 * §10: what a channel needs to publish an Offer, as plain values. `showsFiveStars` is true for a new piece
 * (Nowa), which has no rating of its own: a channel that wants may show five stars for it.
 */
final readonly class OfferAdvertSummary
{
    /**
     * @param  string|null  $condition  `new`, `used` or `outlet`
     * @param  list<string>  $publicPhotos  storage paths in order, the first is the main photo
     */
    public function __construct(
        public int $offerId,
        public string $number,
        public ?GlassModelSummary $glassModel,
        public ?int $producerId,
        public ?string $producer,
        public ?int $year,
        public ?string $condition,
        public ?int $rating,
        public bool $showsFiveStars,
        public ?string $description,
        public array $publicPhotos,
        public ?int $buyersPriceGrosze,
        public bool $reserved,
    ) {}
}
