<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Tables;

use App\Modules\Users\Domain\Enums\RoleEnum;
use App\Modules\Users\Domain\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('role')
                    ->label(__('users::resource.fields.role'))
                    ->formatStateUsing(fn (RoleEnum $state): string => __('users::resource.roles.'.$state->value))
                    ->badge()
                    ->color(fn (RoleEnum $state): string => match ($state) {
                        RoleEnum::Root => 'danger',
                        RoleEnum::Admin => 'warning',
                        RoleEnum::Client => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('name')
                    ->label(__('users::resource.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('users::resource.fields.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label(__('users::resource.fields.phone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('account_type')
                    ->label(__('users::resource.fields.account_type'))
                    ->state(fn (User $record): string => $record->isCompanyAccount()
                        ? __('users::resource.account_types.company')
                        : __('users::resource.account_types.private'))
                    ->badge()
                    ->color(fn (User $record): string => $record->isCompanyAccount() ? 'success' : 'warning'),

                IconColumn::make('email_verified_at')
                    ->label(__('users::resource.fields.email_verified'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('users::resource.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('verified')
                    ->label(__('users::resource.filters.verified'))
                    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

                Filter::make('unverified')
                    ->label(__('users::resource.filters.unverified'))
                    ->query(fn (Builder $query) => $query->whereNull('email_verified_at')),

                Filter::make('company')
                    ->label(__('users::resource.filters.company'))
                    ->query(fn (Builder $query) => $query->where('company_account', true)->where('has_accepted_terms', true)),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
