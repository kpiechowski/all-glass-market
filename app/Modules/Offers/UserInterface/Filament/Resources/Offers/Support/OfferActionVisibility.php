<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;

/**
 * Which approval actions the logged-in user is offered on an Offer: the permission and the same
 * domain rules the entity enforces. Visibility is a convenience; the handlers check again.
 */
final class OfferActionVisibility
{
    /** An admin who can approve a Rejected Offer approves it rather than re-sending it. */
    public static function canSend(OfferModel $offer): bool
    {
        return OfferDomainPolicy::canBeSent($offer->status)
            && $offer->owner_id === (int) auth()->id()
            && ! (self::managesAll() && OfferDomainPolicy::approvalApplies($offer->status));
    }

    public static function canApprove(OfferModel $offer): bool
    {
        return self::managesAll() && OfferDomainPolicy::canBeApproved($offer->status, $offer->glass_model_id !== null);
    }

    /** FR-10: no Glass model yet, so approving starts with choosing or creating one. */
    public static function canApproveWithGlassModel(OfferModel $offer): bool
    {
        return self::managesAll() && OfferDomainPolicy::approvalApplies($offer->status) && $offer->glass_model_id === null;
    }

    public static function canReject(OfferModel $offer): bool
    {
        return self::managesAll() && OfferDomainPolicy::canBeRejected($offer->status);
    }

    /** The page has a primary action of its own, so Edit steps back to secondary. */
    public static function hasPrimaryAction(OfferModel $offer): bool
    {
        return self::canSend($offer) || self::canApprove($offer) || self::canApproveWithGlassModel($offer);
    }

    private static function managesAll(): bool
    {
        return CurrentActor::can(OffersPermission::ManageAll);
    }
}
