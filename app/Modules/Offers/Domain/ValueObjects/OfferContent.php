<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;

/**
 * What the owner writes about the piece (FR-01 to FR-05): the glass, producer, year, condition,
 * rating, descriptions and price. A Draft takes any of it empty (FR-06); what ties fields together
 * is checked here: a rating belongs to a used or outlet piece only (FR-04).
 */
final readonly class OfferContent
{
    public const int OLDEST_YEAR = 1900;

    private function __construct(
        public GlassReference $glass,
        public ?int $producerId,
        public ?int $year,
        public ?OfferCondition $condition,
        public ?Rating $rating,
        public ?string $description,
        public ?string $privateNotes,
        public ?EnteredPrice $price,
    ) {}

    public static function from(
        GlassReference $glass,
        ?int $producerId,
        ?int $year,
        ?OfferCondition $condition,
        ?Rating $rating,
        ?string $description,
        ?string $privateNotes,
        ?EnteredPrice $price,
    ): self {
        if ($year !== null && ($year < self::OLDEST_YEAR || $year > (int) date('Y') + 1)) {
            throw OfferRefused::yearIsInvalid();
        }

        if ($rating !== null && ! OfferDomainPolicy::ratingApplies($condition)) {
            throw $condition === null ? OfferRefused::ratingNeedsCondition() : OfferRefused::ratingNotForNew();
        }

        return new self(
            $glass,
            $producerId,
            $year,
            $condition,
            $rating,
            self::text($description),
            self::text($privateNotes),
            $price,
        );
    }

    private static function text(?string $text): ?string
    {
        $text = $text === null ? '' : trim($text);

        return $text === '' ? null : $text;
    }
}
