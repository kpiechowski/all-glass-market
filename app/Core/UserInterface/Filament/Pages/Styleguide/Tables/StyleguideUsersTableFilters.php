<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Tables;

use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

/** Three simple, frequent filters: inline above the table (template A). DemoUsers applies them. */
final class StyleguideUsersTableFilters
{
    /** @return list<BaseFilter> */
    public static function make(): array
    {
        return [
            SelectFilter::make('account_type')
                ->label('Typ konta')
                ->options(DemoUsers::ACCOUNT_TYPES),
            SelectFilter::make('role')
                ->label('Rola')
                ->options(DemoUsers::ROLES),
            Filter::make('must_change_password')
                ->label('Wymaga zmiany hasła')
                ->toggle(),
        ];
    }
}
