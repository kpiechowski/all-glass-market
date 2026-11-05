<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Tables;

use App\Modules\Audit\Domain\Enums\LogLevel;
use Filament\Tables\Filters\SelectFilter;

class TechnicalLogTableFilters
{
    /** @return list<SelectFilter> */
    public static function make(): array
    {
        return [
            SelectFilter::make('level')
                ->label(__('audit::pages.technical_log.fields.level'))
                ->options(LogLevel::class),
        ];
    }
}
