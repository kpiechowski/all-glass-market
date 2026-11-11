<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Tables;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\DictionaryValueQueries;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\GlassModelQueries;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\VehicleQueries;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * FR-05: filters by position, colour and vehicle make; withdrawn models only on request. Every
 * dictionary value is listed, withdrawn ones too, because models may still hold them. The toggle
 * has no query of its own (a filter that is off is never applied): `GlassModelsTable` reads it. The
 * names are read there too, to hand the same filters to the candidate search.
 */
class GlassModelsTableFilters
{
    public const string POSITION = 'position_id';

    public const string COLOUR = 'colour_id';

    public const string MAKE = 'make_id';

    public const string WITH_WITHDRAWN = 'with_withdrawn';

    /** @return list<BaseFilter> */
    public static function make(): array
    {
        return [
            SelectFilter::make(self::POSITION)
                ->label(__('catalogue::resources.glass_model.filters.position'))
                ->options(fn (): array => self::values(Dictionary::Position))
                ->searchable()
                ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? app(GlassModelQueries::class)->ofPosition($query, (int) $data['value'])
                    : $query),

            SelectFilter::make(self::COLOUR)
                ->label(__('catalogue::resources.glass_model.filters.colour'))
                ->options(fn (): array => self::values(Dictionary::Colour))
                ->searchable()
                ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? app(GlassModelQueries::class)->ofColour($query, (int) $data['value'])
                    : $query),

            SelectFilter::make(self::MAKE)
                ->label(__('catalogue::resources.glass_model.filters.make'))
                ->options(fn (): array => app(VehicleQueries::class)->allMakes()->pluck('name', 'id')->all())
                ->searchable()
                ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? app(GlassModelQueries::class)->fittingMake($query, (int) $data['value'])
                    : $query),

            Filter::make(self::WITH_WITHDRAWN)
                ->label(__('catalogue::resources.glass_model.filters.with_withdrawn'))
                ->toggle(),
        ];
    }

    /** @return array<int, string> */
    private static function values(Dictionary $dictionary): array
    {
        return app(DictionaryValueQueries::class)->ofDictionary($dictionary)->orderBy('name')->pluck('name', 'id')->all();
    }
}
