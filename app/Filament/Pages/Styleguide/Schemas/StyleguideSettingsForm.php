<?php

declare(strict_types=1);

namespace App\Filament\Pages\Styleguide\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

/**
 * Tabs inside a form: only for 4+ independent groups, with no required field after the first tab (template B).
 */
final class StyleguideSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->statePath('settings')
            ->columns(1)
            ->components([
                Tabs::make('Ustawienia sklepu')->tabs([
                    self::generalTab(),
                    self::pricesTab(),
                    self::notificationsTab(),
                    self::integrationsTab(),
                ]),
            ]);
    }

    /** @return array<string, mixed> */
    public static function demoState(): array
    {
        return [
            'shop_name' => 'AllGlass Market',
            'currency' => 'PLN',
            'shows_gross' => true,
            'margin' => '15',
            'notifies_new_order' => true,
            'notifies_low_stock' => false,
            'api_key' => null,
        ];
    }

    private static function generalTab(): Tab
    {
        return Tab::make('Ogólne')
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                TextInput::make('shop_name')->label('Nazwa sklepu')->required()->columnSpan(['default' => 'full', 'md' => 8]),
                Select::make('currency')->label('Waluta')->options(['PLN' => 'PLN', 'EUR' => 'EUR'])->required()->columnSpan(['default' => 'full', 'md' => 4]),
            ]);
    }

    private static function pricesTab(): Tab
    {
        return Tab::make('Ceny')
            ->columns(['default' => 1, 'md' => 12])
            ->schema([
                Toggle::make('shows_gross')->label('Pokazuj ceny brutto')->columnSpanFull(),
                TextInput::make('margin')->label('Domyślna marża')->numeric()->suffix('%')->columnSpan(['default' => 'full', 'md' => 3]),
            ]);
    }

    private static function notificationsTab(): Tab
    {
        return Tab::make('Powiadomienia')
            ->schema([
                Toggle::make('notifies_new_order')->label('Nowe zamówienie'),
                Toggle::make('notifies_low_stock')->label('Niski stan magazynu'),
            ]);
    }

    private static function integrationsTab(): Tab
    {
        return Tab::make('Integracje')
            ->schema([
                TextInput::make('api_key')->label('Klucz API')->password()->revealable(),
            ]);
    }
}
