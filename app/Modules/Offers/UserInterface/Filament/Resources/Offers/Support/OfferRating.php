<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support;

use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;

/** D-46: the rating as stars; a new piece is shown with five. */
final class OfferRating
{
    public static function stars(OfferModel $offer): ?string
    {
        $stars = OfferDomainPolicy::displayedStars($offer->condition, $offer->rating);

        return $stars === null ? null : str_repeat('★', $stars).str_repeat('☆', 5 - $stars);
    }
}
