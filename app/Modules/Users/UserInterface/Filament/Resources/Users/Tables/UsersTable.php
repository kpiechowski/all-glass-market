<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Filament\Resources\Users\Tables;

use App\Core\UserInterface\Filament\Tables\Columns\PersonColumn;
use App\Core\UserInterface\Filament\Tables\Columns\RecordCardColumn;
use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Actions\ImpersonateUserAction;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Support\RoleDisplay;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

/**
 * Template A (list). The active/inactive split is the list tabs of `ListUsers`; "inactive" and
 * "password change required" are flags next to the name (FR-22, 06, 07).
 */
class UsersTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns(self::columns())
            ->filters(UsersTableFilters::make(), layout: FiltersLayout::AboveContent)
            ->recordUrl(fn (UserModel $record): string => UserResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn (UserModel $record): bool => UserResource::canEdit($record)),
                    ImpersonateUserAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedUsers,
            heading: __('users::resources.user.empty.heading'),
            description: __('users::resources.user.empty.description'),
            actions: [CreateAction::make()],
        );
    }

    /** @return array<mixed> */
    private static function columns(): array
    {
        return [
            RecordCardColumn::make()
                ->title(fn (UserModel $record): string => (string) $record->name)
                ->subtitle(fn (UserModel $record): string => (string) $record->email)
                ->flags(fn (UserModel $record): array => self::flags($record))
                ->badges(fn (UserModel $record): array => [RoleDisplay::label($record->role), $record->account_type])
                ->meta(fn (UserModel $record): array => [
                    __('users::resources.user.card.joined', ['date' => $record->created_at?->format('d.m.Y')]),
                ]),

            PersonColumn::make('name')
                ->label(__('users::resources.user.fields.name'))
                ->email(fn (UserModel $record): string => (string) $record->email)
                ->flags(fn (UserModel $record): array => self::flags($record))
                ->searchable(['name', 'email'])
                ->sortable()
                ->visibleFrom('md'),

            TextColumn::make('role')
                ->label(__('users::resources.user.fields.role'))
                ->badge()
                ->formatStateUsing(fn (?string $state): string => RoleDisplay::label($state))
                ->color(fn (?string $state): string => RoleDisplay::color($state))
                ->visibleFrom('md'),

            TextColumn::make('account_type')
                ->label(__('users::resources.user.fields.account_type'))
                ->badge()
                ->visibleFrom('md'),

            TextColumn::make('created_at')
                ->label(__('users::resources.user.fields.created_at'))
                ->date('d.m.Y')
                ->sortable()
                ->visibleFrom('md'),

            TextColumn::make('phone')
                ->label(__('users::resources.user.fields.phone'))
                ->placeholder('—')
                ->toggleable(isToggledHiddenByDefault: true)
                ->visibleFrom('md'),
        ];
    }

    /**
     * The exceptions of a row: inactive (danger) and password change required (warning).
     *
     * @return list<array{label: string, color: string, icon: Heroicon}>
     */
    private static function flags(UserModel $record): array
    {
        $flags = [];

        if (! $record->isActive()) {
            $flags[] = ['label' => __('users::resources.user.status.inactive'), 'color' => 'danger', 'icon' => Heroicon::OutlinedNoSymbol];
        }

        if ($record->password_change_required) {
            $flags[] = ['label' => __('users::resources.user.markers.password_change_required'), 'color' => 'warning', 'icon' => Heroicon::OutlinedKey];
        }

        return $flags;
    }
}
