<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Section variants for the "Sekcje i układ" tab (D-35): each variant next to the default.
 *
 * - Top level: a default section beside a `section--compact` one.
 * - Nested: a default nested section beside a nested `section--compact` one.
 * - Grouping: two `section--flat` groups inside one section (two addresses, never interleaved).
 */
final class StyleguideSectionVariants
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->statePath('sectionVariants')
            ->columns(1)
            ->components([
                self::topLevelComparison(),
                self::nestedComparison(),
                self::flatGroups(),
            ]);
    }

    /** @return array<string, mixed> */
    public static function demoState(): array
    {
        return [
            'default_email' => 'biuro@allglass.pl',
            'default_phone' => '+48 61 222 33 44',
            'compact_email' => 'biuro@allglass.pl',
            'compact_phone' => '+48 61 222 33 44',
            'poznan_stock' => '1240',
            'poznan_bays' => '6',
            'wroclaw_stock' => '860',
            'wroclaw_bays' => '4',
            'seat_street' => 'ul. Głogowska 120',
            'seat_postcode' => '60-243',
            'seat_city' => 'Poznań',
            'invoice_street' => 'ul. Legnicka 48',
            'invoice_postcode' => '54-202',
            'invoice_city' => 'Wrocław',
        ];
    }

    private static function topLevelComparison(): Grid
    {
        return Grid::make(['default' => 1, 'lg' => 2])
            ->schema([
                self::contactSection(Section::make('Kontakt'), 'default')
                    ->description('Domyślna sekcja na tle strony.'),
                self::contactSection(Section::make('Kontakt'), 'compact')
                    ->description('section--compact: ciaśniejsze odstępy, tytuł w rozmiarze tekstu.')
                    ->extraAttributes(['class' => 'section--compact']),
            ]);
    }

    private static function nestedComparison(): Section
    {
        return Section::make('Magazyny')
            ->description('Po lewej domyślna sekcja zagnieżdżona, po prawej zagnieżdżona section--compact.')
            ->schema([
                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        self::warehouseSection(Section::make('Magazyn Poznań'), 'poznan')
                            ->description('ul. Głogowska 120'),
                        self::warehouseSection(Section::make('Magazyn Wrocław'), 'wroclaw')
                            ->description('ul. Legnicka 48')
                            ->extraAttributes(['class' => 'section--compact']),
                    ]),
            ]);
    }

    private static function flatGroups(): Section
    {
        return Section::make('Dane firmy')
            ->description('Dwie grupy section--flat w jednej sekcji: bez ramki, sam tytuł z linią.')
            ->schema([
                self::addressSection(Section::make('Adres siedziby'), 'seat')
                    ->extraAttributes(['class' => 'section--flat']),
                self::addressSection(Section::make('Adres do faktur'), 'invoice')
                    ->extraAttributes(['class' => 'section--flat']),
            ]);
    }

    private static function contactSection(Section $section, string $prefix): Section
    {
        return $section
            ->columns(['default' => 1, 'md' => 2])
            ->schema([
                TextInput::make("{$prefix}_email")->label('E-mail')->email(),
                TextInput::make("{$prefix}_phone")->label('Telefon')->tel(),
            ]);
    }

    private static function warehouseSection(Section $section, string $prefix): Section
    {
        return $section
            ->columns(['default' => 1, 'md' => 2])
            ->schema([
                TextInput::make("{$prefix}_stock")->label('Stan')->numeric()->suffix('szt.'),
                TextInput::make("{$prefix}_bays")->label('Rampy')->numeric(),
            ]);
    }

    private static function addressSection(Section $section, string $prefix): Section
    {
        return $section
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                TextInput::make("{$prefix}_street")->label('Ulica i numer')->columnSpanFull(),
                TextInput::make("{$prefix}_postcode")->label('Kod pocztowy')->placeholder('00-000')->columnSpan(['default' => 'full', 'md' => 3]),
                TextInput::make("{$prefix}_city")->label('Miasto')->columnSpan(['default' => 'full', 'md' => 9]),
            ]);
    }
}
