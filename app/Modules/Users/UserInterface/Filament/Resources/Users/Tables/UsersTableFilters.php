<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Tables;

use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use Filament\Tables\Filters\SelectFilter;

class UsersTableFilters
{
    /** @return array<mixed> */
    public static function make(): array
    {
        return [
            SelectFilter::make('role')
                ->label(__('users::resources.user.filters.role'))
                ->options(fn (): array => RoleDisplay::allOptions()),

            SelectFilter::make('account_type')
                ->label(__('users::resources.user.filters.account_type'))
                ->options(AccountType::class),
        ];
    }
}
