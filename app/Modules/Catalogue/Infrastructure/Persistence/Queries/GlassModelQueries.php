<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Queries;

use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use Illuminate\Database\Eloquent\Builder;

final class GlassModelQueries
{
    /**
     * The plain list (main Eurocode, position, colour); cards, search and filters come with ticket 06.
     *
     * @return Builder<GlassModelModel>
     */
    public function listing(): Builder
    {
        return GlassModelModel::query()->with(['mainEurocode', 'position', 'colour']);
    }

    /**
     * WF-02 move: the models a Eurocode may go to (active or withdrawn), found by a fragment of any of their Eurocodes.
     *
     * @return Builder<GlassModelModel>
     */
    public function moveTargets(string $fragment, int $exceptId): Builder
    {
        $fragment = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($fragment)) ?? '';

        return GlassModelModel::query()
            ->with('mainEurocode')
            ->whereKeyNot($exceptId)
            ->whereHas('eurocodes', fn (Builder $eurocodes) => $eurocodes->where('code', 'like', '%'.$fragment.'%'))
            ->orderBy('id');
    }

    /**
     * @param  Builder<GlassModelModel>  $query
     * @return Builder<GlassModelModel>
     */
    public function onlyWithdrawn(Builder $query): Builder
    {
        return $query->where('withdrawn', true);
    }

    /**
     * @param  Builder<GlassModelModel>  $query
     * @return Builder<GlassModelModel>
     */
    public function onlyActive(Builder $query): Builder
    {
        return $query->where('withdrawn', false);
    }
}
