<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Tables;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    /**
     * Seam: the "wymaga zmiany hasła" marker and filter arrive with ticket 06.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('users::resources.user.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('users::resources.user.fields.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('account_type')
                    ->label(__('users::resources.user.fields.account_type'))
                    ->badge(),

                TextColumn::make('role')
                    ->label(__('users::resources.user.fields.role'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => RoleDisplay::label($state))
                    ->color(fn (?string $state): string => RoleDisplay::color($state)),

                TextColumn::make('deactivated_at')
                    ->label(__('users::resources.user.fields.status'))
                    ->badge()
                    ->color('danger')
                    ->state(fn (UserModel $record): ?string => $record->isActive() ? null : __('users::resources.user.status.inactive')),

                TextColumn::make('created_at')
                    ->label(__('users::resources.user.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters(UsersTableFilters::make())
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
