<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Support;

use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;

/** Which answers the logged-in user is offered on an Inquiry; the handlers check again. */
final class InquiryActionVisibility
{
    /** FR-22: the owner accepts only while the Inquiry is open and the Offer is Active. */
    public static function canAnswer(InquiryModel $inquiry): bool
    {
        return self::isOwnerAndOpen($inquiry)
            && OfferDomainPolicy::canReceiveInquiry($inquiry->offer->status, $inquiry->offer->trashed_at !== null);
    }

    /** FR-22: the owner rejects any open Inquiry. */
    public static function canReject(InquiryModel $inquiry): bool
    {
        return self::isOwnerAndOpen($inquiry);
    }

    private static function isOwnerAndOpen(InquiryModel $inquiry): bool
    {
        return $inquiry->state->isOpen() && $inquiry->offer->owner_id === (int) auth()->id();
    }
}
