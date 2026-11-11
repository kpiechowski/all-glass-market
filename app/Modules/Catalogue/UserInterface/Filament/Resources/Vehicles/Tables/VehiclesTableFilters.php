<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Tables;

use App\Modules\Catalogue\Infrastructure\Persistence\Queries\VehicleQueries;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

/** FR-09: filters by make and body. Every value is listed, withdrawn ones too, because vehicles may still have them. */
class VehiclesTableFilters
{
    /** @return list<SelectFilter> */
    public static function make(): array
    {
        return [
            SelectFilter::make('make_id')
                ->label(__('catalogue::resources.vehicle.filters.make'))
                ->options(fn (): array => app(VehicleQueries::class)->allMakes()->pluck('name', 'id')->all())
                ->searchable()
                ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? app(VehicleQueries::class)->ofMake($query, (int) $data['value'])
                    : $query),

            SelectFilter::make('body_id')
                ->label(__('catalogue::resources.vehicle.filters.body'))
                ->options(fn (): array => app(VehicleQueries::class)->allBodies()->pluck('name', 'id')->all())
                ->searchable()
                ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? app(VehicleQueries::class)->ofBody($query, (int) $data['value'])
                    : $query),
        ];
    }
}
