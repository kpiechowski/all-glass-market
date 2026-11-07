<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Schemas;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;

/**
 * Record tabs (template C): uncontained, tab 1 is the record sheet (infolist), the later tabs
 * hold the audit history and related data. Nested inside the Styleguide's own page tabs.
 */
final class StyleguideTabs
{
    public static function make(Component $details): Tabs
    {
        return Tabs::make('Zakładki rekordu')
            ->contained(false)
            ->extraAttributes(['class' => 'tabs--underline'])
            ->tabs([
                Tab::make('Szczegóły')->schema([$details]),
                Tab::make('Historia zmian')->badge('3')->schema([
                    Text::make('Druga zakładka: dziennik zmian rekordu.'),
                ]),
                Tab::make('Zamówienia')->schema([
                    Text::make('Kolejne zakładki: dane powiązane (relation managers).'),
                ]),
            ]);
    }
}
