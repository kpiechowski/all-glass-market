<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Tables;

use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

/** Three inline filters; active/inactive is a list tab, not a filter (template A). */
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

            TernaryFilter::make('password_change_required')
                ->label(__('users::resources.user.filters.password_change_required'))
                ->queries(
                    true: fn (Builder $query): Builder => $query->where('password_change_required', true),
                    false: fn (Builder $query): Builder => $query->where('password_change_required', false),
                    blank: fn (Builder $query): Builder => $query,
                ),
        ];
    }
}
