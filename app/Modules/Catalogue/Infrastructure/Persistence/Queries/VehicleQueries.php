<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Queries;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use Illuminate\Database\Eloquent\Builder;

final class VehicleQueries
{
    /** @return Builder<VehicleModel> */
    public function listing(): Builder
    {
        return VehicleModel::query()->with(['make', 'body']);
    }

    /**
     * @param  Builder<VehicleModel>  $query
     * @return Builder<VehicleModel>
     */
    public function ofMake(Builder $query, int $makeId): Builder
    {
        return $query->where('make_id', $makeId);
    }

    /**
     * @param  Builder<VehicleModel>  $query
     * @return Builder<VehicleModel>
     */
    public function ofBody(Builder $query, int $bodyId): Builder
    {
        return $query->where('body_id', $bodyId);
    }

    /**
     * Makes offered for a new choice (FR-24): the active ones, plus the one the vehicle already has.
     *
     * @return Builder<DictionaryValueModel>
     */
    public function offeredMakes(?int $currentId = null): Builder
    {
        return $this->offered(Dictionary::VehicleMake, $currentId);
    }

    /**
     * @return Builder<DictionaryValueModel>
     */
    public function offeredBodies(?int $currentId = null): Builder
    {
        return $this->offered(Dictionary::VehicleBody, $currentId);
    }

    /** Every make, withdrawn ones too: a filter must find vehicles that already have them. @return Builder<DictionaryValueModel> */
    public function allMakes(): Builder
    {
        return DictionaryValueModel::query()->where('dictionary', Dictionary::VehicleMake)->orderBy('name');
    }

    /** @return Builder<DictionaryValueModel> */
    public function allBodies(): Builder
    {
        return DictionaryValueModel::query()->where('dictionary', Dictionary::VehicleBody)->orderBy('name');
    }

    /** @return Builder<DictionaryValueModel> */
    private function offered(Dictionary $dictionary, ?int $currentId): Builder
    {
        return DictionaryValueModel::query()
            ->where('dictionary', $dictionary)
            ->where(fn (Builder $query) => $query
                ->where('withdrawn', false)
                ->when($currentId !== null, fn (Builder $query) => $query->orWhereKey($currentId)))
            ->orderBy('name');
    }
}
