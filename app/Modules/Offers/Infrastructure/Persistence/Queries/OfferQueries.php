<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Queries;

use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use Illuminate\Database\Eloquent\Builder;

final class OfferQueries
{
    /**
     * RULE-11: a seller gets their own Offers only; whoever manages all Offers gets every one.
     *
     * @return Builder<OfferModel>
     */
    public function visibleTo(int $userId, bool $managesAll): Builder
    {
        return OfferModel::query()
            ->with(['owner', 'producer'])
            ->when(! $managesAll, fn (Builder $query) => $query->where('owner_id', $userId));
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
        return $query->where('status', OfferStatus::Pending);
    }
}
