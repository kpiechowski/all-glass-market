<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Queries;

use App\Modules\Catalogue\Infrastructure\Persistence\Models\ThumbnailMappingModel;
use Illuminate\Database\Eloquent\Builder;

final class ThumbnailMappingQueries
{
    /**
     * What the catalogue settings page fills its fields from.
     *
     * @return Builder<ThumbnailMappingModel>
     */
    public function listing(): Builder
    {
        return ThumbnailMappingModel::query();
    }
}
