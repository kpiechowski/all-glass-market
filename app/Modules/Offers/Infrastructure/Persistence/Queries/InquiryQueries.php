<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Queries;

use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use Illuminate\Database\Eloquent\Builder;

final class InquiryQueries
{
    /**
     * FR-22, FR-21a: a seller gets the Inquiries on their own Offers (never those of an Offer in the Trash);
     * whoever manages all Offers gets every one.
     *
     * @return Builder<InquiryModel>
     */
    public function visibleTo(int $userId, bool $managesAll): Builder
    {
        return InquiryModel::query()
            ->with(['offer', 'sender'])
            ->when(! $managesAll, fn (Builder $query) => $query->whereHas('offer', fn (Builder $offers) => $offers
                ->whereNull('trashed_at')
                ->where('owner_id', $userId)));
    }

    /**
     * FR-21: the Inquiries still waiting on the Offer, oldest first, with their senders, so the admin sees how
     * many are open and from whom before sending another.
     *
     * @return Builder<InquiryModel>
     */
    public function openOfOffer(int $offerId): Builder
    {
        return InquiryModel::query()
            ->with('sender')
            ->where('offer_id', $offerId)
            ->whereIn('state', [InquiryState::Sent, InquiryState::Read])
            ->orderBy('id');
    }
}
