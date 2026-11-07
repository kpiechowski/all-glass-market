<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;

/**
 * Tab variants for the "Sekcje i układ" tab (D-35): the same switcher inside a section three
 * times, as the default plain underline, as `tabs--underline` (the page-tab look) and as
 * `tabs--pills`. Static content only, no state.
 */
final class StyleguideTabVariants
{
    public static function make(): Grid
    {
        return Grid::make(['default' => 1, 'xl' => 3])
            ->schema([
                Section::make('Sprzedaż')
                    ->description('Domyślne zakładki w sekcji: zwykłe podkreślenie.')
                    ->schema([self::periodTabs('default')]),
                Section::make('Sprzedaż')
                    ->description('tabs--underline: wygląd zakładek strony, także w sekcji.')
                    ->schema([self::periodTabs('underline')->extraAttributes(['class' => 'tabs--underline'])]),
                Section::make('Sprzedaż')
                    ->description('tabs--pills: przełącznik widoku w sekcji.')
                    ->schema([self::periodTabs('pills')->extraAttributes(['class' => 'tabs--pills'])]),
            ]);
    }

    private static function periodTabs(string $variant): Tabs
    {
        return Tabs::make('Okres')
            ->key("period-tabs-{$variant}")
            ->contained(false)
            ->tabs([
                Tab::make('Dziś')->key("{$variant}-today")->schema([
                    Text::make('12 zamówień, 18 400 zł.'),
                ]),
                Tab::make('7 dni')->key("{$variant}-week")->badge('3')->schema([
                    Text::make('84 zamówienia, 131 200 zł.'),
                ]),
                Tab::make('30 dni')->key("{$variant}-month")->schema([
                    Text::make('352 zamówienia, 548 900 zł.'),
                ]),
            ]);
    }
}
