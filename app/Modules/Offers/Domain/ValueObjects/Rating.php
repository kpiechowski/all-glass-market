<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Exceptions\OfferRefused;

/** FR-04: 1 to 5 stars. */
final readonly class Rating
{
    private function __construct(public int $stars) {}

    public static function from(int $stars): self
    {
        if ($stars < 1 || $stars > 5) {
            throw OfferRefused::ratingIsInvalid();
        }

        return new self($stars);
    }
}
