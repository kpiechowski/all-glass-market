<?php

declare(strict_types=1);

namespace App\Filament\Pages\Styleguide\Tables;

use App\Core\UserInterface\Filament\Tables\Columns\PersonColumn;
use App\Core\UserInterface\Filament\Tables\Columns\RecordCardColumn;
use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Filament\Pages\Styleguide;
use App\Filament\Pages\Styleguide\Actions\DeactivateUserDemoAction;
use App\Filament\Pages\Styleguide\Actions\DemoNotification;
use App\Filament\Pages\Styleguide\Actions\InviteUserDemoAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

/**
 * Template A on static rows. The page adds `records()` (list tab, empty-state switch) and
 * the demo switches; everything else (columns, filters, actions, empty state) is here.
 *
 * The name cell is shown two ways, switched by "Komórka: …": the composite person cell
 * (recommended for lists of people) and the native `->description()` with an icon indicator.
 */
final class StyleguideUsersTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->heading('Użytkownicy')
            ->columns(self::columns())
            ->filters(StyleguideUsersTableFilters::make(), layout: FiltersLayout::AboveContent)
            ->searchable()
            ->searchPlaceholder('Szukaj po nazwisku lub e-mailu')
            ->recordUrl(fn (): string => '?obszar=karta-rekordu')
            ->recordActions(self::recordActions())
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activateSelected')
                        ->label('Aktywuj zaznaczonych')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->action(fn () => DemoNotification::success('Aktywowano zaznaczonych użytkowników')),
                    BulkAction::make('deactivateSelected')
                        ->label('Dezaktywuj zaznaczonych')
                        ->icon(Heroicon::OutlinedNoSymbol)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Dezaktywować zaznaczonych użytkowników?')
                        ->modalDescription('Zaznaczeni użytkownicy nie zalogują się, dopóki ich nie aktywujesz.')
                        ->modalSubmitActionLabel('Dezaktywuj')
                        ->action(fn () => DemoNotification::success('Dezaktywowano zaznaczonych użytkowników')),
                ]),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->defaultSort('name');

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedUsers,
            heading: 'Nie ma jeszcze użytkowników',
            description: 'Zaproś pierwszą osobę, a pojawi się na tej liście.',
            actions: [InviteUserDemoAction::make('inviteFromEmptyState')],
        );
    }

    /** @return list<Column> */
    private static function columns(): array
    {
        return [
            self::card(),
            PersonColumn::make('person')
                ->label('Użytkownik')
                ->state(fn (array $record): string => $record['name'])
                ->email(fn (array $record): string => $record['email'])
                ->flags(fn (array $record): array => $record['must_change_password']
                    ? [['label' => 'Zmiana hasła', 'icon' => Heroicon::OutlinedKey, 'color' => 'warning']]
                    : [])
                ->sortable()
                ->visible(fn (Styleguide $livewire): bool => $livewire->showsPersonCell)
                ->visibleFrom('md'),
            TextColumn::make('name')
                ->label('Użytkownik')
                ->description(fn (array $record): string => $record['email'])
                ->icon(fn (array $record): ?Heroicon => $record['must_change_password'] ? Heroicon::OutlinedKey : null)
                ->iconPosition(IconPosition::After)
                ->iconColor('warning')
                ->tooltip(fn (array $record): ?string => $record['must_change_password'] ? 'Wymaga zmiany hasła' : null)
                ->sortable()
                ->visible(fn (Styleguide $livewire): bool => ! $livewire->showsPersonCell)
                ->visibleFrom('md'),
            TextColumn::make('account_type')
                ->label('Typ konta')
                ->badge()
                ->color('gray')
                ->formatStateUsing(fn (string $state): string => DemoUsers::ACCOUNT_TYPES[$state])
                ->visibleFrom('md'),
            TextColumn::make('role')
                ->label('Rola')
                ->badge()
                ->color('gray')
                ->formatStateUsing(fn (string $state): string => DemoUsers::ROLES[$state])
                ->visibleFrom('md'),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => DemoUsers::STATUSES[$state]['color'])
                ->formatStateUsing(fn (string $state): string => DemoUsers::STATUSES[$state]['label'])
                ->visibleFrom('md'),
            TextColumn::make('orders_count')
                ->label('Zamówienia')
                ->numeric()
                ->alignEnd()
                ->sortable()
                ->visibleFrom('md'),
            TextColumn::make('joined_at')
                ->label('Data dołączenia')
                ->date('d.m.Y')
                ->sortable()
                ->visibleFrom('md'),
            TextColumn::make('updated_at')
                ->label('Ostatnia zmiana')
                ->since()
                ->dateTooltip('d.m.Y H:i')
                ->toggleable(isToggledHiddenByDefault: true)
                ->visibleFrom('md'),
        ];
    }

    /** The one cell shown below `md` (D-12). */
    private static function card(): RecordCardColumn
    {
        return RecordCardColumn::make()
            ->title(fn (array $record): string => $record['name'])
            ->subtitle(fn (array $record): string => $record['email'])
            ->status(fn (array $record): array => DemoUsers::STATUSES[$record['status']])
            ->flags(fn (array $record): array => $record['must_change_password']
                ? [['label' => 'Zmiana hasła', 'icon' => Heroicon::OutlinedKey, 'color' => 'warning']]
                : [])
            ->badges(fn (array $record): array => [
                DemoUsers::ACCOUNT_TYPES[$record['account_type']],
                DemoUsers::ROLES[$record['role']],
            ])
            ->meta(fn (array $record): array => [
                'od '.Carbon::parse($record['joined_at'])->format('d.m.Y'),
            ]);
    }

    /**
     * One standalone icon action with a tooltip, the rest in one "⋮" group.
     *
     * @return list<Action|ActionGroup>
     */
    private static function recordActions(): array
    {
        return [
            Action::make('sendPasswordLink')
                ->label('Wyślij link do zmiany hasła')
                ->icon(Heroicon::OutlinedKey)
                ->iconButton()
                ->tooltip('Wyślij link do zmiany hasła')
                ->action(fn () => DemoNotification::success('Wysłano link do zmiany hasła')),
            ActionGroup::make([
                Action::make('edit')
                    ->label('Edytuj')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->action(fn () => DemoNotification::success('Otwarto edycję')),
                Action::make('impersonate')
                    ->label('Wejdź na konto')
                    ->icon(Heroicon::OutlinedArrowRightEndOnRectangle)
                    ->action(fn () => DemoNotification::success('Przełączono na konto użytkownika')),
                DeactivateUserDemoAction::make('deactivateRow'),
            ]),
        ];
    }
}
