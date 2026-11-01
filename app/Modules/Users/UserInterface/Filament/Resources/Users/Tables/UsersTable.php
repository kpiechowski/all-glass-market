<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Tables;

use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    /**
     * Seams: the "wymaga zmiany hasła" and inactive markers and filters arrive
     * with tickets 06 and 07.
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
