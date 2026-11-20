<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Policies;

use App\Modules\Offers\Domain\Enums\OfferCondition;

final class OfferDomainPolicy
{
    /** FR-04: only a used or outlet piece carries a rating. */
    public static function ratingApplies(?OfferCondition $condition): bool
    {
        return $condition === OfferCondition::Used || $condition === OfferCondition::Outlet;
    }

    /** D-46: a new piece stores no rating and is shown with five stars. */
    public static function displayedStars(?OfferCondition $condition, ?int $rating): ?int
    {
        return $condition === OfferCondition::New ? 5 : $rating;
    }

    /** RULE-11, FR-05: a seller sees only their own Offers and private notes; whoever manages all Offers sees every one. */
    public static function canBeSeenBy(int $ownerId, int $userId, bool $managesAll): bool
    {
        return $managesAll || $ownerId === $userId;
    }
}
