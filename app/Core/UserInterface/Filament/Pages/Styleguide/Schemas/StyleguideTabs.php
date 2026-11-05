<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Schemas;

use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;

/** Page-level tabs: uncontained, as on a record page (template C) where each tab is a whole view. */
final class StyleguideTabs
{
    public static function make(): Tabs
    {
        return Tabs::make('Zakładki strony')
            ->contained(false)
            ->tabs([
                Tab::make('Szczegóły')->schema([
                    Text::make('Zakładka 1 to zawsze karta rekordu (infolista).'),
                ]),
                Tab::make('Historia zmian')->badge('3')->schema([
                    Text::make('Druga zakładka: dziennik zmian rekordu.'),
                ]),
                Tab::make('Zamówienia')->schema([
                    Text::make('Kolejne zakładki: dane powiązane (relation managers).'),
                ]),
            ]);
    }
}
