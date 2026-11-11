<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence\Queries;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use Illuminate\Database\Eloquent\Builder;

final class DictionaryValueQueries
{
    /** @return Builder<DictionaryValueModel> */
    public function listing(): Builder
    {
        return DictionaryValueModel::query();
    }

    /**
     * @param  Builder<DictionaryValueModel>|null  $query
     * @return Builder<DictionaryValueModel>
     */
    public function ofDictionary(Dictionary $dictionary, ?Builder $query = null): Builder
    {
        return ($query ?? $this->listing())->where('dictionary', $dictionary);
    }

    /**
     * @param  Builder<DictionaryValueModel>  $query
     * @return Builder<DictionaryValueModel>
     */
    public function onlyWithdrawn(Builder $query): Builder
    {
        return $query->where('withdrawn', true);
    }

    /**
     * @param  Builder<DictionaryValueModel>  $query
     * @return Builder<DictionaryValueModel>
     */
    public function onlyActive(Builder $query): Builder
    {
        return $query->where('withdrawn', false);
    }

    /**
     * What a form offers for picking (FR-24): the dictionary's active values, and the withdrawn ones
     * the record being edited already holds.
     *
     * @param  list<int>  $alsoIds
     * @return Builder<DictionaryValueModel>
     */
    public function choosable(Dictionary $dictionary, array $alsoIds = []): Builder
    {
        return $this->ofDictionary($dictionary)
            ->where(fn (Builder $query): Builder => $query->where('withdrawn', false)->orWhereIn('id', $alsoIds))
            ->orderBy('name');
    }
}
