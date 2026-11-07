<?php

declare(strict_types=1);

namespace App\Filament\Pages\Styleguide\Schemas;

use App\Filament\Pages\Styleguide\Actions\DemoNotification;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Section layouts for the "Sekcje i układ" tab (D-43): a section with header actions, a nested
 * section with a fieldset and a footer action, and a collapsed section of rare settings.
 */
final class StyleguideLayoutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->statePath('layoutDemo')
            ->columns(1)
            ->components([
                self::shippingSection(),
                self::pickupSection(),
                self::advancedSection(),
            ]);
    }

    /** @return array<string, mixed> */
    public static function demoState(): array
    {
        return [
            'shipping_free_from' => '500',
            'shipping_handling_days' => '2',
            'pickup_opens_at' => '08:00',
            'pickup_closes_at' => '16:00',
            'pickup_on_saturday' => false,
            'hides_prices' => false,
            'internal_code' => null,
        ];
    }

    /** A plain section: heading, description, a header action and a 12-column grid. */
    private static function shippingSection(): Section
    {
        return self::grid(Section::make('Wysyłka'))
            ->description('Sekcja z opisem i akcją w nagłówku; pola na siatce 12 kolumn.')
            ->headerActions([
                Action::make('resetShipping')
                    ->label('Przywróć domyślne')
                    ->link()
                    ->action(fn () => DemoNotification::success('Przywrócono ustawienia wysyłki')),
            ])
            ->schema([
                TextInput::make('shipping_free_from')->label('Darmowa wysyłka od')->numeric()->suffix('zł')->columnSpan(self::span(4)),
                TextInput::make('shipping_handling_days')->label('Czas realizacji')->numeric()->suffix('dni')->columnSpan(self::span(4)),
            ]);
    }

    /** A nested section with a fieldset, and a footer action that concerns this section only. */
    private static function pickupSection(): Section
    {
        return Section::make('Punkty odbioru')
            ->description('Każdy magazyn to sekcja zagnieżdżona; godziny otwarcia to grupa pól.')
            ->footerActions([
                Action::make('addPickupPoint')
                    ->label('Dodaj punkt odbioru')
                    ->color('gray')
                    ->action(fn () => DemoNotification::success('Dodano punkt odbioru')),
            ])
            ->schema([
                self::grid(Section::make('Magazyn Poznań'))
                    ->description('ul. Głogowska 120, 60-243 Poznań')
                    ->schema([
                        Fieldset::make('Godziny otwarcia')
                            ->columns(['default' => 1, 'md' => 2])
                            ->columnSpan(self::span(6))
                            ->schema([
                                TextInput::make('pickup_opens_at')->label('Od')->placeholder('08:00'),
                                TextInput::make('pickup_closes_at')->label('Do')->placeholder('16:00'),
                            ]),
                        Toggle::make('pickup_on_saturday')->label('Odbiór w soboty')->columnSpanFull(),
                    ]),
            ]);
    }

    /** Rarely used, optional settings: the one case for a collapsed section. */
    private static function advancedSection(): Section
    {
        return self::grid(Section::make('Ustawienia zaawansowane'))
            ->collapsible()
            ->collapsed()
            ->schema([
                Toggle::make('hides_prices')->label('Ukryj ceny dla niezalogowanych')->columnSpanFull(),
                TextInput::make('internal_code')->label('Kod wewnętrzny')->placeholder('AG-0000')->columnSpan(self::span(4)),
            ]);
    }

    /**
     * Full width on mobile, the given span of 12 from `md`.
     *
     * @return array{default: string, md: int}
     */
    private static function span(int $md): array
    {
        return ['default' => 'full', 'md' => $md];
    }

    private static function grid(Section $section): Section
    {
        return $section->columns(['default' => 1, 'md' => 12]);
    }
}
