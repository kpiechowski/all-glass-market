<?php

declare(strict_types=1);

namespace App\Filament\Pages\Styleguide\Schemas;

use App\Filament\Pages\Styleguide\Actions\DeactivateUserDemoAction;
use App\Filament\Pages\Styleguide\Actions\DemoNotification;
use App\Filament\Pages\Styleguide\Actions\EditPricingDemoAction;
use App\Filament\Pages\Styleguide\Actions\InviteUserDemoAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Menus, modals, notifications and the stat widget for the "Akcje i modale" tab (D-34, D-43).
 * Every action only sends a demo notification (D-33).
 */
final class StyleguideOverlays
{
    /**
     * The "⋮" menu as it sits in a table row or a section: ordinary items first, the dangerous
     * ones in their own group below a hairline, red text only (D-16, D-17).
     */
    public static function rowMenu(): Actions
    {
        return Actions::make([
            self::menu('demoRowMenu'),
        ]);
    }

    /**
     * The page-header menu: ordinary items, then the dangerous group. Shared by the Styleguide
     * header so the "⋮" there shows the same structure.
     */
    public static function menu(string $prefix = 'menu'): ActionGroup
    {
        return ActionGroup::make([
            ActionGroup::make([
                Action::make("{$prefix}Duplicate")
                    ->label('Duplikuj')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->action(fn () => DemoNotification::success('Zduplikowano')),
                Action::make("{$prefix}Archive")
                    ->label('Archiwizuj')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->action(fn () => DemoNotification::success('Zarchiwizowano')),
            ])->dropdown(false),
            ActionGroup::make([
                DeactivateUserDemoAction::make("{$prefix}Deactivate"),
                self::deleteAction("{$prefix}Delete"),
            ])->dropdown(false),
        ]);
    }

    /** Modal (short form), slide-over (longer form beside a list); confirmations open from "⋮" above. */
    public static function modals(): Actions
    {
        return Actions::make([
            InviteUserDemoAction::make('demoModal')->label('Otwórz modal')->color('gray'),
            EditPricingDemoAction::make('demoSlideOver')->label('Otwórz panel boczny'),
        ]);
    }

    /** One trigger per status; the copy follows D-22 (past tense, an error says what to do). */
    public static function notifications(): Actions
    {
        return Actions::make([
            Action::make('demoNotificationSuccess')
                ->label('Sukces')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('gray')
                ->action(fn () => DemoNotification::success('Zapisano zmiany')),
            Action::make('demoNotificationDanger')
                ->label('Błąd')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('gray')
                ->action(fn () => Notification::make()
                    ->danger()
                    ->title('Nie udało się wysłać zaproszenia')
                    ->body('Sprawdź adres e-mail i spróbuj ponownie.')
                    ->send()),
            Action::make('demoNotificationWarning')
                ->label('Ostrzeżenie')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('gray')
                ->action(fn () => Notification::make()
                    ->warning()
                    ->title('Zapisano bez zdjęcia')
                    ->body('Dodaj zdjęcie profilowe, zanim konto zostanie aktywowane.')
                    ->send()),
            Action::make('demoNotificationInfo')
                ->label('Informacja')
                ->icon(Heroicon::OutlinedInformationCircle)
                ->color('gray')
                ->action(fn () => Notification::make()
                    ->info()
                    ->title('Eksport trwa w tle')
                    ->body('Plik pojawi się w powiadomieniach, gdy będzie gotowy.')
                    ->send()),
        ]);
    }

    /** A basic stat widget row (template F is a sketch only): a plain value, a rising and a falling trend. */
    public static function stats(): Grid
    {
        return Grid::make(['default' => 1, 'md' => 3])->schema([
            Stat::make('Aktywni użytkownicy', '128')
                ->icon(Heroicon::OutlinedUsers)
                ->description('Bez zmian od wczoraj'),
            Stat::make('Nowe zamówienia', '42')
                ->icon(Heroicon::OutlinedShoppingCart)
                ->description('12% więcej niż w zeszłym tygodniu')
                ->descriptionIcon(Heroicon::ArrowTrendingUp)
                ->descriptionColor('success'),
            Stat::make('Zaległe faktury', '7')
                ->icon(Heroicon::OutlinedDocumentText)
                ->description('3 po terminie')
                ->descriptionIcon(Heroicon::ExclamationTriangle)
                ->descriptionColor('danger'),
        ]);
    }

    private static function deleteAction(string $name): Action
    {
        return Action::make($name)
            ->label('Usuń')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedTrash)
            ->modalHeading('Usunąć użytkownika?')
            ->modalDescription('Konto i jego historia zostaną usunięte na stałe. Tej operacji nie można cofnąć.')
            ->modalSubmitActionLabel('Usuń na stałe')
            ->action(fn () => DemoNotification::success('Usunięto użytkownika'));
    }
}
