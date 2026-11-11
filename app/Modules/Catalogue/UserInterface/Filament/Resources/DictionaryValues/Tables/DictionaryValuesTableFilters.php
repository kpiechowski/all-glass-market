<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Tables;

use App\Modules\Catalogue\Infrastructure\Persistence\Queries\DictionaryValueQueries;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class DictionaryValuesTableFilters
{
    /** @return list<TernaryFilter> */
    public static function make(): array
    {
        return [
            TernaryFilter::make('withdrawn')
                ->label(__('catalogue::resources.dictionary_value.filters.state'))
                ->placeholder(__('catalogue::resources.dictionary_value.filters.all'))
                ->trueLabel(__('catalogue::resources.dictionary_value.filters.withdrawn'))
                ->falseLabel(__('catalogue::resources.dictionary_value.filters.active'))
                ->queries(
                    true: fn (Builder $query): Builder => app(DictionaryValueQueries::class)->onlyWithdrawn($query),
                    false: fn (Builder $query): Builder => app(DictionaryValueQueries::class)->onlyActive($query),
                    blank: fn (Builder $query): Builder => $query,
                ),
        ];
    }
}
