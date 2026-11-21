<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Queries;

use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use Illuminate\Database\Eloquent\Builder;

final class OfferQueries
{
    /**
     * RULE-11: a seller gets their own Offers only, never one in the Trash (FR-25); whoever manages all
     * Offers gets every one, the Trash included, so a deleted Offer can still be opened and restored.
     * The lists narrow an admin's query with `outsideTrash` and `onlyTrash`.
     *
     * @return Builder<OfferModel>
     */
    public function visibleTo(int $userId, bool $managesAll): Builder
    {
        return OfferModel::query()
            ->with(['owner', 'producer', 'currentReservation'])
            ->when(! $managesAll, fn (Builder $query) => $this->outsideTrash($query)->where('owner_id', $userId));
    }

    /**
     * FR-11: the approval queue, the Pending tab and its counter. Admins only; the caller decides who may see it.
     *
     * @return Builder<OfferModel>
     */
    public function pendingApproval(): Builder
    {
        return $this->onlyPending(OfferModel::query()->with(['owner', 'producer']));
    }

    /**
     * @param  Builder<OfferModel>  $query
     * @return Builder<OfferModel>
     */
    public function onlyPending(Builder $query): Builder
    {
        return $this->outsideTrash($query)->where('status', OfferStatus::Pending);
    }

    /**
     * FR-25: every list but the Trash tab leaves out deleted Offers.
     *
     * @param  Builder<OfferModel>  $query
     * @return Builder<OfferModel>
     */
    public function outsideTrash(Builder $query): Builder
    {
        return $query->whereNull('trashed_at');
    }

    /**
     * FR-25: the admin's Trash tab.
     *
     * @param  Builder<OfferModel>  $query
     * @return Builder<OfferModel>
     */
    public function onlyTrash(Builder $query): Builder
    {
        return $query->whereNotNull('trashed_at');
    }
}
