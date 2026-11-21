<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support;

use App\Core\Contracts\Authorizer;
use App\Core\Domain\Actor;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Enums\OfferStatus;
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
        return ! self::isInTrash($offer)
            && OfferDomainPolicy::canBeSent($offer->status)
            && $offer->owner_id === (int) auth()->id()
            && ! (self::managesAll() && OfferDomainPolicy::approvalApplies($offer->status));
    }

    public static function canApprove(OfferModel $offer): bool
    {
        return ! self::isInTrash($offer) && self::managesAll() && OfferDomainPolicy::canBeApproved($offer->status, $offer->glass_model_id !== null);
    }

    /** FR-10: no Glass model yet, so approving starts with choosing or creating one. */
    public static function canApproveWithGlassModel(OfferModel $offer): bool
    {
        return ! self::isInTrash($offer) && self::managesAll() && OfferDomainPolicy::approvalApplies($offer->status) && $offer->glass_model_id === null;
    }

    public static function canReject(OfferModel $offer): bool
    {
        return ! self::isInTrash($offer) && self::managesAll() && OfferDomainPolicy::canBeRejected($offer->status);
    }

    /** FR-24: whoever sees the Offer (its owner, or an admin) may withdraw it. */
    public static function canWithdraw(OfferModel $offer): bool
    {
        return OfferDomainPolicy::canBeWithdrawn($offer->status, self::isInTrash($offer));
    }

    /**
     * FR-24, FR-13: the same decision the entity takes. An admin's own Offer (any admin owner) resumes
     * with a Glass model; every other Offer only when approved before and unchanged since.
     */
    public static function canResume(OfferModel $offer): bool
    {
        if (! OfferDomainPolicy::canBeResumed($offer->status, self::isInTrash($offer))) {
            return false;
        }

        return OfferDomainPolicy::resumesWithoutApproval($offer->last_approved_at !== null, $offer->changed_since_approval)
            || ($offer->glass_model_id !== null && self::ownerManagesAll($offer));
    }

    /** FR-14: only an admin changes the Glass model, in any status outside the Trash. */
    public static function canChangeGlassModel(OfferModel $offer): bool
    {
        return self::managesAll() && OfferDomainPolicy::glassModelCanBeChanged(self::isInTrash($offer));
    }

    /** FR-27: whoever sees the Offer (its owner, or an admin) duplicates it, unless it is in the Trash. */
    public static function canDuplicate(OfferModel $offer): bool
    {
        return OfferDomainPolicy::canBeDuplicated(self::isInTrash($offer));
    }

    /** FR-25: only an admin deletes. */
    public static function canDelete(OfferModel $offer): bool
    {
        return self::managesAll() && OfferDomainPolicy::canBeTrashed($offer->status, self::isInTrash($offer));
    }

    public static function canRestore(OfferModel $offer): bool
    {
        return self::managesAll() && OfferDomainPolicy::canBeRestored(self::isInTrash($offer));
    }

    public static function canDeletePermanently(OfferModel $offer): bool
    {
        return self::managesAll() && OfferDomainPolicy::canBeDeletedPermanently(self::isInTrash($offer));
    }

    /** FR-16: whoever sees an Active Offer may reserve it. */
    public static function canReserve(OfferModel $offer): bool
    {
        return OfferDomainPolicy::canBeReserved($offer->status, self::isInTrash($offer));
    }

    /** FR-21: only an admin sends an Inquiry, and only about an Active Offer. */
    public static function canSendInquiry(OfferModel $offer): bool
    {
        return self::managesAll() && OfferDomainPolicy::canReceiveInquiry($offer->status, self::isInTrash($offer));
    }

    /** FR-17, RULE-05: an admin's Reservation is visible but locked for its seller. */
    public static function canRelease(OfferModel $offer): bool
    {
        return OfferDomainPolicy::canBeReleased($offer->status, self::isInTrash($offer)) && self::reservationIsOpenToMe($offer);
    }

    /** FR-18: Active or Reserved, the seller not over an admin's Reservation. */
    public static function canSell(OfferModel $offer): bool
    {
        return OfferDomainPolicy::canBeSold($offer->status, self::isInTrash($offer)) && self::reservationIsOpenToMe($offer);
    }

    /** FR-19: only an admin returns a Sale. */
    public static function canReturnSale(OfferModel $offer): bool
    {
        return self::managesAll() && OfferDomainPolicy::canBeReturned($offer->status, self::isInTrash($offer));
    }

    /** Whether the Offer's current Reservation, if any, is locked for the logged-in user. */
    public static function reservationIsLocked(OfferModel $offer): bool
    {
        return $offer->status === OfferStatus::Reserved && ! self::reservationIsOpenToMe($offer);
    }

    /**
     * RULE-14: a bulk action is offered on a tab only when it could act on some Offer there. Nothing in the
     * Trash can be acted on in bulk; a status tab asks the same rule the handler applies, "all" always offers it.
     *
     * @param  callable(OfferStatus): bool  $appliesToStatus
     */
    public static function bulkApplies(?string $tab, callable $appliesToStatus): bool
    {
        if (OfferListTabs::isTrash($tab)) {
            return false;
        }

        $status = OfferListTabs::statusOf($tab);

        return $status === null || $appliesToStatus($status);
    }

    private static function reservationIsOpenToMe(OfferModel $offer): bool
    {
        return OfferDomainPolicy::reservationCanBeHandledBy((bool) $offer->currentReservation?->made_by_admin, self::managesAll());
    }

    public static function isInTrash(OfferModel $offer): bool
    {
        return $offer->trashed_at !== null;
    }

    /** The page has a primary action of its own, so Edit steps back to secondary. */
    public static function hasPrimaryAction(OfferModel $offer): bool
    {
        return self::canSend($offer) || self::canApprove($offer) || self::canApproveWithGlassModel($offer)
            || self::canResume($offer) || self::canRestore($offer);
    }

    private static function ownerManagesAll(OfferModel $offer): bool
    {
        return app(Authorizer::class)->can(Actor::user($offer->owner_id), OffersPermission::ManageAll);
    }

    private static function managesAll(): bool
    {
        return CurrentActor::can(OffersPermission::ManageAll);
    }
}
