<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Policies;

use App\Modules\Offers\Domain\Enums\OfferCondition;

final class OfferDomainPolicy
{
    /** FR-05: how many public and how many private photos an Offer holds. */
    public const int MAX_PHOTOS_PER_KIND = 10;

    /** Where the public photos a user uploads are stored. */
    public static function publicPhotoDirectory(int $uploaderId): string
    {
        return "offers/public/{$uploaderId}";
    }

    /** Where the private photos a user uploads are stored. */
    public static function privatePhotoDirectory(int $uploaderId): string
    {
        return "offers/private/{$uploaderId}";
    }

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

    /**
     * FR-05: every path not already on the Offer must sit directly in the uploader's folder.
     *
     * @param  list<string>  $paths
     * @param  list<string>  $current
     */
    public static function photosAreFromUploader(array $paths, array $current, string $uploaderDirectory): bool
    {
        foreach ($paths as $path) {
            if (! in_array($path, $current, true) && ! str_starts_with($path, $uploaderDirectory.'/')) {
                return false;
            }
        }

        return true;
    }
}
