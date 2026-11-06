<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Schemas;

use App\Core\UserInterface\Filament\Pages\Styleguide\Actions\DemoNotification;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Flex;
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

    /** States are coloured, categories are neutral (admin-ui-design, colour = state). */
    public static function badges(): Flex
    {
        return Flex::make([
            Text::make('Aktywny')->badge()->color('success')->grow(false),
            Text::make('Wymaga działania')->badge()->color('warning')->grow(false),
            Text::make('Zablokowany')->badge()->color('danger')->grow(false),
            Text::make('Zaproszony')->badge()->color('info')->grow(false),
            Text::make('Firma')->badge()->color('gray')->grow(false),
            Text::make('Klient')->badge()->color('gray')->grow(false),
        ]);
    }

    private static function demo(string $name, string $label): Action
    {
        return Action::make($name)
            ->label($label)
            ->action(fn () => DemoNotification::success("Kliknięto: {$label}"));
    }
}
