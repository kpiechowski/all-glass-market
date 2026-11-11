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
