<?php

declare(strict_types=1);

namespace App\Filament\Pages\Styleguide\Schemas;

use App\Filament\Pages\Styleguide\Tables\DemoUsers;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

/**
 * Template C, layout (b): status, type, role, confirmations and dates stay in view in the side
 * column, which drops above the main column on mobile. The identity header (avatar, name,
 * e-mail) opens the record. Sheet sections run two sheet columns from xl with entries paired by
 * meaning; long values span the full width. Every empty value shows "—".
 *
 * Every native entry type appears once for the infolist review (issue 07): text with copy,
 * links, dates, lists and long text; booleans with "Tak" / "Nie"; images; key-value; repeatable
 * items and table. "Rozliczenia" is a grid of short metrics rather than a sheet.
 */
final class StyleguideInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->state(self::record())
            ->columns(1)
            ->components([
                self::identityHeader(),
                Grid::make(['default' => 1, 'lg' => 3])->schema([
                    Group::make([
                        self::companySection(),
                        self::billingSection(),
                        self::deliveryAddressesSection(),
                        self::contactsSection(),
                        self::integrationSection(),
                        self::notesSection(),
                    ])
                        ->columnSpan(['default' => 'full', 'lg' => 2])
                        ->columnOrder(['default' => 2, 'lg' => 1]),
                    Group::make([self::accountSection()])
                        ->columnSpan(['default' => 'full', 'lg' => 1])
                        ->columnOrder(['default' => 1, 'lg' => 2]),
                ]),
            ]);
    }

    /** Avatar, name and the main contact in one block; the labels stay for screen readers. */
    private static function identityHeader(): Section
    {
        return Section::make()
            ->extraAttributes(['class' => 'infolist--identity'])
            ->schema([
                Flex::make([
                    ImageEntry::make('avatar')->label('Zdjęcie profilowe')->hiddenLabel()->circular()->imageSize(56)->grow(false),
                    Group::make([
                        TextEntry::make('name')->label('Imię i nazwisko')->hiddenLabel()->size(TextSize::Large)->weight(FontWeight::Bold),
                        TextEntry::make('email')->label('E-mail')->hiddenLabel()->copyable()->copyMessage('Skopiowano adres'),
                    ]),
                ])->verticallyAlignCenter(),
            ]);
    }

    /** Side column: status and metadata, one sheet column (it is narrow). */
    private static function accountSection(): Section
    {
        return Section::make('Konto')
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => DemoUsers::STATUSES[$state]['color'])
                    ->formatStateUsing(fn (string $state): string => DemoUsers::STATUSES[$state]['label'])
                    ->helperText('Wymaga zmiany hasła przy następnym logowaniu.')
                    ->icon(Heroicon::OutlinedKey)
                    ->iconColor('warning'),
                TextEntry::make('account_type')
                    ->label('Typ konta')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => DemoUsers::ACCOUNT_TYPES[$state]),
                TextEntry::make('role')
                    ->label('Rola')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => DemoUsers::ROLES[$state]),
                self::booleanEntry('email_verified', 'E-mail potwierdzony'),
                self::booleanEntry('marketing_consent', 'Zgoda marketingowa'),
                TextEntry::make('joined_at')->label('Data dołączenia')->date('d.m.Y'),
                TextEntry::make('updated_at')->label('Ostatnia zmiana')->since()->dateTooltip('d.m.Y H:i'),
            ]);
    }

    /**
     * A boolean reads as text with an icon (D-10a E): "Tak" with a green check, "Nie" with a grey
     * cross. Red is for a "no" that is an operational problem, not for every false.
     */
    private static function booleanEntry(string $name, string $label): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->formatStateUsing(fn (bool $state): string => __($state ? 'filament-infolists::components.entries.icon.true' : 'filament-infolists::components.entries.icon.false'))
            ->icon(fn (bool $state): Heroicon => $state ? Heroicon::CheckCircle : Heroicon::XCircle)
            ->iconColor(fn (bool $state): string => $state ? 'success' : 'gray');
    }

    /** Short values paired by meaning in two sheet columns from xl; long values span the row. */
    private static function companySection(): Section
    {
        return Section::make('Firma')
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->columns(['default' => 1, 'xl' => 2])
            ->schema([
                TextEntry::make('company_name')->label('Nazwa firmy'),
                TextEntry::make('company_nip')->label('NIP')->copyable()->copyMessage('Skopiowano NIP'),
                TextEntry::make('invoice_email')
                    ->label('E-mail do faktur')
                    ->url(fn (string $state): string => 'mailto:'.$state),
                TextEntry::make('phone')->label('Telefon')->placeholder('—'),
                TextEntry::make('company_website')
                    ->label('Strona internetowa')
                    ->url(fn (string $state): string => $state)
                    ->openUrlInNewTab(),
                TextEntry::make('company_categories')
                    ->label('Kategorie produktów')
                    ->badge()
                    ->color('gray'),
                TextEntry::make('company_address')->label('Adres')->placeholder('—')->columnSpanFull(),
                TextEntry::make('company_brands')
                    ->label('Obsługiwane marki')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->columnSpanFull(),
                ImageEntry::make('company_photos')
                    ->label('Zdjęcia punktu')
                    ->imageHeight(72)
                    ->limit(3)
                    ->limitedRemainingText()
                    ->columnSpanFull(),
            ]);
    }

    /** Short metrics side by side: entries in a responsive grid, not a sheet. */
    private static function billingSection(): Section
    {
        return Section::make('Rozliczenia')
            ->columns(['default' => 1, 'sm' => 2, '2xl' => 4])
            ->schema([
                TextEntry::make('orders_count')->label('Zamówienia')->numeric(),
                TextEntry::make('turnover')->label('Obrót w 2026')->money('PLN', locale: 'pl'),
                TextEntry::make('credit_limit')->label('Limit kupiecki')->money('PLN', locale: 'pl')->placeholder('—'),
                TextEntry::make('last_order_at')->label('Ostatnie zamówienie')->date('d.m.Y'),
            ]);
    }

    /** The section's only entry is named like the section, so its label is hidden. */
    private static function deliveryAddressesSection(): Section
    {
        return Section::make('Adresy dostawy')
            ->schema([
                RepeatableEntry::make('delivery_addresses')
                    ->label('Adresy dostawy')
                    ->hiddenLabel()
                    ->grid(['default' => 1, '2xl' => 2])
                    ->columns(['default' => 1, 'sm' => 3])
                    ->schema([
                        TextEntry::make('name')->label('Nazwa')->columnSpan(['default' => 'full', 'sm' => 3]),
                        TextEntry::make('street')->label('Ulica i numer')->columnSpan(['default' => 'full', 'sm' => 3]),
                        TextEntry::make('postcode')->label('Kod pocztowy')->columnSpan(['default' => 'full', 'sm' => 1]),
                        TextEntry::make('city')->label('Miejscowość')->columnSpan(['default' => 'full', 'sm' => 2]),
                        TextEntry::make('courier_note')
                            ->label('Uwagi dla kuriera')
                            ->placeholder('—')
                            ->columnSpan(['default' => 'full', 'sm' => 3]),
                    ]),
            ]);
    }

    /** The section's only entry is named like the section, so its label is hidden. */
    private static function contactsSection(): Section
    {
        return Section::make('Osoby kontaktowe')
            ->schema([
                RepeatableEntry::make('contacts')
                    ->label('Osoby kontaktowe')
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make('Imię i nazwisko'),
                        TableColumn::make('Stanowisko'),
                        TableColumn::make('Telefon'),
                    ])
                    ->schema([
                        TextEntry::make('name')->label('Imię i nazwisko'),
                        TextEntry::make('position')->label('Stanowisko'),
                        TextEntry::make('phone')->label('Telefon')->placeholder('—'),
                    ]),
            ]);
    }

    private static function integrationSection(): Section
    {
        return Section::make('Integracja z Subiektem')
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                KeyValueEntry::make('sync_parameters')
                    ->label('Parametry synchronizacji')
                    ->keyLabel('Parametr')
                    ->valueLabel('Wartość'),
                KeyValueEntry::make('extra_fields')
                    ->label('Pola dodatkowe')
                    ->keyLabel('Pole')
                    ->valueLabel('Wartość')
                    ->placeholder('—'),
            ]);
    }

    /** The section's only entry is named like the section, so its label is hidden. */
    private static function notesSection(): Section
    {
        return Section::make('Notatki')
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                TextEntry::make('notes')->label('Notatki')->hiddenLabel()->placeholder('—'),
            ]);
    }

    /**
     * The demo record: the shown user plus the extra values this review needs. Images are inline
     * SVG data URIs, so nothing is fetched or stored.
     *
     * @return array<string, mixed>
     */
    private static function record(): array
    {
        return [
            ...DemoUsers::shownRecord(),
            'avatar' => self::svgDataUri(self::avatarSvg('PN')),
            'email_verified' => true,
            'marketing_consent' => false,
            'invoice_email' => 'ksiegowosc@autoglass.pl',
            'company_website' => 'https://www.autoglass-nowak.pl',
            'company_brands' => ['Pilkington', 'Saint-Gobain Sekurit', 'AGC Automotive', 'Fuyao'],
            'company_categories' => ['Szyby czołowe', 'Szyby boczne', 'Kleje i uszczelki'],
            'company_photos' => [
                self::svgDataUri(self::photoSvg('#dce6fe', '#5a82ec')),
                self::svgDataUri(self::photoSvg('#d5ebf6', '#2581b5')),
                self::svgDataUri(self::photoSvg('#e3e5e9', '#6e737d')),
                self::svgDataUri(self::photoSvg('#bccffc', '#003eb2')),
            ],
            'orders_count' => 1284,
            'turnover' => 486320.5,
            'credit_limit' => null,
            'last_order_at' => '2026-11-03',
            'delivery_addresses' => [
                [
                    'name' => 'Warsztat główny',
                    'street' => 'ul. Przemysłowa 14',
                    'postcode' => '60-301',
                    'city' => 'Poznań',
                    'courier_note' => 'Wjazd od ul. Kolejowej, rampa nr 2. Odbiór w godzinach 7:00–15:00.',
                ],
                [
                    'name' => 'Magazyn Swarzędz',
                    'street' => 'ul. Rabowicka 8B',
                    'postcode' => '62-020',
                    'city' => 'Swarzędz',
                    'courier_note' => null,
                ],
            ],
            'contacts' => [
                ['name' => 'Piotr Nowak', 'position' => 'Właściciel', 'phone' => '+48 601 234 567'],
                ['name' => 'Marta Nowak', 'position' => 'Księgowość', 'phone' => '+48 61 852 40 10'],
                ['name' => 'Adam Kubiak', 'position' => 'Magazynier', 'phone' => null],
            ],
            'sync_parameters' => [
                'Kod kontrahenta' => 'K-00412',
                'Cennik' => 'Hurt B',
                'Termin płatności' => '14 dni',
                'Ostatnia synchronizacja' => '06.11.2026 06:00',
            ],
            'extra_fields' => [],
            'notes' => 'Stały klient od 2024 roku, zamawia głównie szyby czołowe do aut dostawczych i osobowych segmentu C. '
                .'Prosi o kontakt telefoniczny przed każdą dostawą powyżej 20 sztuk, bo ma ograniczone miejsce na rampie. '
                .'W marcu 2026 zgłosił reklamację partii uszczelek (zamknięta, wymiana towaru). '
                .'Faktury wyłącznie elektronicznie na adres księgowości; papierowych dokumentów nie wysyłamy.',
        ];
    }

    private static function svgDataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private static function avatarSvg(string $initials): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96">'
            .'<rect width="96" height="96" fill="#dce6fe"/>'
            .'<text x="48" y="58" text-anchor="middle" font-family="Manrope, Arial, sans-serif" font-size="32" font-weight="700" fill="#001b4e">'.$initials.'</text>'
            .'</svg>';
    }

    /** A stand-in photo: a tinted pane with a glass highlight, 4:3. */
    private static function photoSvg(string $background, string $pane): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 72">'
            .'<rect width="96" height="72" fill="'.$background.'"/>'
            .'<path d="M14 58 L24 16 H72 L82 58 Z" fill="'.$pane.'" fill-opacity="0.55"/>'
            .'<path d="M30 22 H44 L34 52 H22 Z" fill="#fff" fill-opacity="0.45"/>'
            .'</svg>';
    }
}
