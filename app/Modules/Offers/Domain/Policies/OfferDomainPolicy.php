<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Policies;

use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\ValueObjects\OfferContent;
use App\Modules\Offers\Domain\ValueObjects\OfferPhotos;

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

    /** FR-06: the cheapest entered price a sent Offer may have, in grosze (1 zł). */
    public const int MIN_SENT_PRICE_GROSZE = 100;

    /**
     * FR-06: what a Draft still lacks before it can be sent, as field keys in form order:
     * `glass`, `price`, `condition`, `rating`, `public_photos`.
     *
     * @return list<string>
     */
    public static function missingForSending(OfferContent $content, OfferPhotos $photos): array
    {
        $missing = [];

        if ($content->glass->glassModelId === null && $content->glass->identificationHint === null) {
            $missing[] = 'glass';
        }

        if ($content->price === null || $content->price->grosze < self::MIN_SENT_PRICE_GROSZE) {
            $missing[] = 'price';
        }

        if ($content->condition === null) {
            $missing[] = 'condition';
        } elseif (self::ratingApplies($content->condition) && $content->rating === null) {
            $missing[] = 'rating';
        }

        if ($photos->public === []) {
            $missing[] = 'public_photos';
        }

        return $missing;
    }

    /** §7: Draft or Rejected goes to the queue (a seller) or live (an admin's own Offer). */
    public static function canBeSent(OfferStatus $status): bool
    {
        return $status === OfferStatus::Draft || $status === OfferStatus::Rejected;
    }

    /** §7: only Pending and Rejected Offers wait for an approval decision. */
    public static function approvalApplies(OfferStatus $status): bool
    {
        return $status === OfferStatus::Pending || $status === OfferStatus::Rejected;
    }

    /** FR-09, RULE-03: approval needs a Glass model. */
    public static function canBeApproved(OfferStatus $status, bool $hasGlassModel): bool
    {
        return self::approvalApplies($status) && $hasGlassModel;
    }

    /** FR-09. */
    public static function canBeRejected(OfferStatus $status): bool
    {
        return $status === OfferStatus::Pending || $status === OfferStatus::Active;
    }

    /**
     * FR-07, D-49: a seller's change needs a new approval unless it touches only the entered price,
     * private notes or private photos. Public photos count in their order.
     */
    public static function changeNeedsReapproval(
        OfferContent $before,
        OfferPhotos $beforePhotos,
        OfferContent $after,
        OfferPhotos $afterPhotos,
    ): bool {
        return $before->glass->glassModelId !== $after->glass->glassModelId
            || $before->glass->identificationHint !== $after->glass->identificationHint
            || $before->producerId !== $after->producerId
            || $before->year !== $after->year
            || $before->condition !== $after->condition
            || $before->rating?->stars !== $after->rating?->stars
            || $before->description !== $after->description
            || $beforePhotos->public !== $afterPhotos->public;
    }
}
