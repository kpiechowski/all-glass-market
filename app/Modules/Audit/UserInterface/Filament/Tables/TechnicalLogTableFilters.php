<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Tables;

use App\Modules\Audit\Application\Queries\TechnicalLog\LogFiles;
use App\Modules\Audit\Domain\Enums\LogLevel;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

/**
 * Channel and day are the selection the log cannot be read without: defaults, no chip, never
 * "narrowed" (A, Tabs and filters). The page reads them from the table's filter state.
 */
class TechnicalLogTableFilters
{
    /** @return list<Filter|SelectFilter> */
    public static function make(): array
    {
        return [
            Filter::make('channel')
                ->schema([
                    Select::make('channel')
                        ->label(__('audit::pages.history_and_logs.technical_logs.fields.channel'))
                        ->options(fn (): array => app(LogFiles::class)->channels())
                        ->default(fn (): string => app(LogFiles::class)->defaultChannel())
                        ->selectablePlaceholder(false),
                ])
                ->indicateUsing(fn (): array => []),
            Filter::make('day')
                ->schema([
                    DatePicker::make('day')
                        ->label(__('audit::pages.history_and_logs.technical_logs.fields.day'))
                        ->default(fn (): string => now()->format('Y-m-d'))
                        ->maxDate(now()),
                ])
                ->indicateUsing(fn (): array => []),
            SelectFilter::make('level')
                ->label(__('audit::pages.history_and_logs.technical_logs.fields.level'))
                ->options(LogLevel::class),
        ];
    }
}
