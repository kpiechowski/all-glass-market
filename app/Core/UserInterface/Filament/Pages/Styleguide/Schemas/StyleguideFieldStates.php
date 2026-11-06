<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Field states for the "Formularze" tab (issue 06): labels and helper text, prefixes and
 * suffixes, every choice control, readonly and disabled fields, and validation errors.
 * A review board, not a template screen: it is never submitted, and the errors are added
 * by the page on mount (`Styleguide::showDemoErrors()`).
 */
final class StyleguideFieldStates
{
    private const array WAREHOUSES = [
        'poznan' => 'Magazyn Poznań',
        'wroclaw' => 'Magazyn Wrocław',
        'gdansk' => 'Magazyn Gdańsk',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->statePath('fieldStates')
            ->columns(1)
            ->components([
                self::labelsSection(),
                self::affixesSection(),
                self::choicesSection(),
                self::datesSection(),
                self::readonlyAndDisabledSection(),
                self::errorsSection(),
            ]);
    }

    /** @return array<string, mixed> */
    public static function demoState(): array
    {
        return [
            'display_name' => 'Szyby Kowalski',
            'customer_order_number' => null,
            'invoice_note' => null,
            'price' => '1249.00',
            'website' => 'szyby-kowalski',
            'search' => null,
            'contact_email' => 'biuro@szyby-kowalski.pl',
            'api_key' => 'ag_live_4f9c2d7e1b',
            'discount' => '5',
            'accepts_terms' => true,
            'newsletter' => false,
            'channels' => ['email'],
            'priority' => 'normal',
            'is_visible' => true,
            'is_featured' => false,
            'delivery_date' => now()->addDays(3)->format('Y-m-d'),
            'pickup_at' => now()->addDay()->setTime(9, 30)->format('Y-m-d H:i:s'),
            'native_date' => now()->addWeek()->format('Y-m-d'),
            'readonly_number' => 'ZAM/2026/11/0042',
            'readonly_note' => 'Klient prosi o kontakt telefoniczny przed dostawą.',
            'disabled_name' => 'Anna Kowalska',
            'disabled_warehouse' => 'poznan',
            'disabled_date' => now()->format('Y-m-d'),
            'disabled_price' => null,
            'disabled_checked' => true,
            'disabled_unchecked' => false,
            'disabled_toggle' => true,
            'disabled_priority' => 'high',
            'error_email' => 'anna.kowalska@',
            'error_warehouse' => null,
            'error_terms' => false,
            'error_priority' => null,
            'error_note' => null,
        ];
    }

    /**
     * Errors shown on first load; each one says what to do next.
     *
     * @return array<string, string>
     */
    public static function demoErrors(): array
    {
        return [
            'fieldStates.error_email' => 'Wpisz pełny adres e-mail, np. anna@firma.pl.',
            'fieldStates.error_warehouse' => 'Wybierz magazyn, z którego wyślemy towar.',
            'fieldStates.error_terms' => 'Zaznacz zgodę, aby wysłać zamówienie.',
            'fieldStates.error_priority' => 'Wybierz priorytet zamówienia.',
            'fieldStates.error_note' => 'Skróć uwagi do 200 znaków.',
        ];
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

    private static function labelsSection(): Section
    {
        return self::grid(Section::make('Etykiety i podpowiedzi'))
            ->description('Pole wymagane ma gwiazdkę, opcjonalne nie ma nic. Podpowiedź to najwyżej jedno zdanie.')
            ->schema([
                TextInput::make('display_name')->label('Nazwa wyświetlana')->required()->columnSpan(self::span(6)),
                TextInput::make('customer_order_number')
                    ->label('Numer zamówienia klienta')
                    ->placeholder('np. PO-2026-118')
                    ->columnSpan(self::span(6)),
                TextInput::make('invoice_note')
                    ->label('Dopisek na fakturze')
                    ->helperText('Tekst pojawi się pod pozycjami faktury i w potwierdzeniu e-mail, dlatego nie wpisuj tu danych, których klient nie powinien zobaczyć.')
                    ->columnSpanFull(),
            ]);
    }

    private static function affixesSection(): Section
    {
        return self::grid(Section::make('Prefiksy i sufiksy'))
            ->description('Tekst jako osobna komórka, ikona i akcja w polu.')
            ->schema([
                TextInput::make('price')->label('Cena netto')->numeric()->suffix('zł')->columnSpan(self::span(4)),
                TextInput::make('website')->label('Adres sklepu')->prefix('https://')->suffix('.pl')->columnSpan(self::span(8)),
                TextInput::make('search')
                    ->label('Szukaj')
                    ->placeholder('Nazwa, kod lub NIP')
                    ->prefixIcon(Heroicon::OutlinedMagnifyingGlass)
                    ->columnSpan(self::span(6)),
                TextInput::make('contact_email')
                    ->label('E-mail kontaktowy')
                    ->email()
                    ->suffixIcon(Heroicon::OutlinedEnvelope)
                    ->columnSpan(self::span(6)),
                TextInput::make('api_key')->label('Klucz API')->password()->revealable()->columnSpan(self::span(6)),
                TextInput::make('discount')
                    ->label('Rabat')
                    ->numeric()
                    ->prefixIcon(Heroicon::OutlinedReceiptPercent)
                    ->inlinePrefix()
                    ->suffix('%')
                    ->inlineSuffix()
                    ->columnSpan(self::span(3)),
            ]);
    }

    private static function choicesSection(): Section
    {
        return self::grid(Section::make('Pola wyboru'))
            ->description('Pole wyboru, lista z opisami, opcje i przełączniki. Stan pośredni pola wyboru widać w nagłówku tabeli przy zaznaczeniu części wierszy.')
            ->schema([
                Checkbox::make('accepts_terms')->label('Akceptuję regulamin sprzedaży')->columnSpan(self::span(6)),
                Checkbox::make('newsletter')->label('Wysyłaj nowości e-mailem')->columnSpan(self::span(6)),
                CheckboxList::make('channels')
                    ->label('Powiadomienia')
                    ->options([
                        'email' => 'E-mail',
                        'sms' => 'SMS',
                        'panel' => 'W panelu',
                    ])
                    ->descriptions([
                        'email' => 'Na adres z danych kontaktowych.',
                        'sms' => 'Tylko pilne: zmiana terminu dostawy.',
                        'panel' => 'Dzwonek w górnym pasku.',
                    ])
                    ->columnSpan(self::span(6)),
                Radio::make('priority')
                    ->label('Priorytet')
                    ->options([
                        'low' => 'Niski',
                        'normal' => 'Normalny',
                        'high' => 'Wysoki',
                    ])
                    ->descriptions([
                        'high' => 'Realizacja w ciągu 24 godzin.',
                    ])
                    ->columnSpan(self::span(6)),
                Toggle::make('is_visible')->label('Widoczna w sklepie')->columnSpan(self::span(6)),
                Toggle::make('is_featured')->label('Wyróżniona na stronie głównej')->columnSpan(self::span(6)),
            ]);
    }

    private static function datesSection(): Section
    {
        return self::grid(Section::make('Daty'))
            ->description('Kalendarz panelu i pole natywne przeglądarki; ta sama wysokość co przycisk.')
            ->schema([
                DatePicker::make('delivery_date')
                    ->label('Termin dostawy')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->columnSpan(self::span(4)),
                DateTimePicker::make('pickup_at')
                    ->label('Odbiór')
                    ->native(false)
                    ->seconds(false)
                    ->displayFormat('d.m.Y H:i')
                    ->columnSpan(self::span(4)),
                DatePicker::make('native_date')->label('Data (pole natywne)')->columnSpan(self::span(4)),
            ]);
    }

    private static function readonlyAndDisabledSection(): Section
    {
        return self::grid(Section::make('Tylko do odczytu i wyłączone'))
            ->description('Tylko do odczytu: jasne tło, tekst bez zmian. Wyłączone: szare tło, tekst nadal czytelny.')
            ->schema([
                TextInput::make('readonly_number')->label('Numer zamówienia')->readOnly()->columnSpan(self::span(6)),
                TextInput::make('disabled_name')->label('Opiekun klienta')->disabled()->columnSpan(self::span(6)),
                Textarea::make('readonly_note')->label('Uwagi klienta')->readOnly()->rows(2)->columnSpan(self::span(6)),
                Select::make('disabled_warehouse')
                    ->label('Magazyn')
                    ->options(self::WAREHOUSES)
                    ->disabled()
                    ->columnSpan(self::span(6)),
                DatePicker::make('disabled_date')
                    ->label('Data przyjęcia')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->disabled()
                    ->columnSpan(self::span(6)),
                TextInput::make('disabled_price')->label('Cena katalogowa')->suffix('zł')->placeholder('0,00')->disabled()->columnSpan(self::span(6)),
                Checkbox::make('disabled_checked')->label('Zaznaczone, wyłączone')->disabled()->columnSpan(self::span(4)),
                Checkbox::make('disabled_unchecked')->label('Puste, wyłączone')->disabled()->columnSpan(self::span(4)),
                Toggle::make('disabled_toggle')->label('Przełącznik wyłączony')->disabled()->columnSpan(self::span(4)),
                Radio::make('disabled_priority')
                    ->label('Priorytet (wyłączony)')
                    ->options(['normal' => 'Normalny', 'high' => 'Wysoki'])
                    ->inline()
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    private static function errorsSection(): Section
    {
        return self::grid(Section::make('Błędy walidacji'))
            ->description('Czerwona ramka i komunikat, który mówi, co zrobić.')
            ->schema([
                TextInput::make('error_email')->label('E-mail do faktur')->email()->required()->columnSpan(self::span(6)),
                Select::make('error_warehouse')
                    ->label('Magazyn wysyłki')
                    ->options(self::WAREHOUSES)
                    ->required()
                    ->columnSpan(self::span(6)),
                Radio::make('error_priority')
                    ->label('Priorytet zamówienia')
                    ->options(['normal' => 'Normalny', 'high' => 'Wysoki'])
                    ->inline()
                    ->required()
                    ->columnSpan(self::span(6)),
                Checkbox::make('error_terms')->label('Akceptuję warunki dostawy')->accepted()->columnSpan(self::span(6)),
                Textarea::make('error_note')->label('Uwagi do zamówienia')->rows(2)->columnSpanFull(),
            ]);
    }
}
