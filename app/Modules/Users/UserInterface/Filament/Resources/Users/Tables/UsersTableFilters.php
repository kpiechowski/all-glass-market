<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Tables;

use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

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

            TernaryFilter::make('is_active')
                ->label(__('users::resources.user.filters.status'))
                ->placeholder(__('users::resources.user.filters.status_all'))
                ->trueLabel(__('users::resources.user.filters.status_active'))
                ->falseLabel(__('users::resources.user.filters.status_inactive'))
                ->queries(
                    true: fn ($query) => $query->whereNull('deactivated_at'),
                    false: fn ($query) => $query->whereNotNull('deactivated_at'),
                    blank: fn ($query) => $query,
                ),

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
