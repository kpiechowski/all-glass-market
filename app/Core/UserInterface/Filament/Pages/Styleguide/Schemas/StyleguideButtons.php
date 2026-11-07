<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Schemas;

use App\Core\UserInterface\Filament\Pages\Styleguide\Actions\DemoNotification;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;

/**
 * The button set and the badge set for the "Akcje i modale" tab (D-43). Every button only sends
 * a demo notification. The filled red button stands for a confirmation button only.
 */
final class StyleguideButtons
{
    /** Primary, grey, link, icon button and the danger confirmation, at the default size. */
    public static function kinds(): Actions
    {
        return Actions::make([
            self::demo('buttonPrimary', 'Zapisz'),
            self::demo('buttonGray', 'Anuluj')->color('gray'),
            self::demo('buttonGrayIcon', 'Eksportuj')->color('gray')->icon(Heroicon::OutlinedArrowDownTray),
            self::demo('buttonLink', 'Pokaż szczegóły')->link(),
            self::demo('buttonIcon', 'Edytuj')->iconButton()->icon(Heroicon::OutlinedPencilSquare)->color('gray')->tooltip('Edytuj'),
            self::demo('buttonDanger', 'Usuń na stałe')->color('danger'),
        ]);
    }

    /** Every size, primary next to grey. */
    public static function sizes(): Actions
    {
        $actions = [];

        foreach ([Size::ExtraSmall, Size::Small, Size::Medium, Size::Large, Size::ExtraLarge] as $size) {
            $actions[] = self::demo("buttonPrimary{$size->name}", "Zapisz {$size->value}")->size($size);
            $actions[] = self::demo("buttonGray{$size->name}", "Anuluj {$size->value}")->color('gray')->size($size);
        }

        return Actions::make($actions);
    }

    /**
     * Categories against states, side by side (D-21): a category is grey or a soft primary tint
     * with no dot; a state is success, warning, danger or info and carries the state dot.
     */
    public static function badges(): Grid
    {
        return Grid::make(['default' => 1, 'md' => 2])->schema([
            Group::make([
                Text::make('Kategorie (rodzaj konta, rola)'),
                Flex::make([
                    Text::make('Firma')->badge()->color('gray')->grow(false),
                    Text::make('Osoba prywatna')->badge()->color('gray')->grow(false),
                    Text::make('Klient')->badge()->color('gray')->grow(false),
                    Text::make('Administrator')->badge()->color('primary')->grow(false),
                ]),
            ]),
            Group::make([
                Text::make('Stany (operacyjne)'),
                Flex::make([
                    Text::make('Aktywny')->badge()->color('success')->grow(false),
                    Text::make('Wymaga działania')->badge()->color('warning')->grow(false),
                    Text::make('Zablokowany')->badge()->color('danger')->grow(false),
                    Text::make('Zaproszony')->badge()->color('info')->grow(false),
                ]),
            ]),
        ]);
    }

    private static function demo(string $name, string $label): Action
    {
        return Action::make($name)
            ->label($label)
            ->action(fn () => DemoNotification::success("Kliknięto: {$label}"));
    }
}
