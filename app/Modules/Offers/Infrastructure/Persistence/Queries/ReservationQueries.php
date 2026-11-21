<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence\Queries;

use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use Illuminate\Database\Eloquent\Builder;

final class ReservationQueries
{
    /**
     * FR-32: the current Reservations on the user's own Offers, with their authors; whoever manages all
     * Offers gets every one. Ended Reservations and those of Offers in the Trash are left out.
     *
     * @return Builder<ReservationModel>
     */
    public function currentVisibleTo(int $userId, bool $managesAll): Builder
    {
        return ReservationModel::query()
            ->with(['offer', 'author'])
            ->where('is_current', true)
            ->whereHas('offer', fn (Builder $offers) => $offers
                ->whereNull('trashed_at')
                ->when(! $managesAll, fn (Builder $query) => $query->where('owner_id', $userId)));
    }
}
