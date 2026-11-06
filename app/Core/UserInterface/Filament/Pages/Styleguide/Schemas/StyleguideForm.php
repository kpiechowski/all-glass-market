<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Schemas;

use App\Core\UserInterface\Filament\Pages\Styleguide\Actions\DemoNotification;
use App\Core\UserInterface\Filament\Pages\Styleguide\Tables\DemoUsers;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Template B, layout (a): one column of stacked sections on a 12-column grid.
 * Covers every common field type, helper text, a validation error (NIP), a field that
 * appears reactively and a section header action. Section layouts (nested, fieldset, footer
 * actions, collapsed) are in StyleguideLayoutForm.
 */
final class StyleguideForm
{
    private const string NIP_PATTERN = '/^\d{10}$/';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(1)
            ->components([
                self::contactSection(),
                self::accountTypeSection(),
                self::companySection(),
                self::companyAddressSection(),
                self::offerSection(),
                self::descriptionSection(),
                self::attachmentsSection(),
            ]);
    }

    /**
     * Prefilled demo state; the NIP is invalid on purpose so the error state can be shown.
     *
     * @return array<string, mixed>
     */
    public static function demoState(): array
    {
        return [
            'first_name' => 'Anna',
            'last_name' => 'Kowalska',
            'email' => 'anna.kowalska@szyby-kowalski.pl',
            'phone' => null,
            'account_type' => 'company',
            'company_name' => 'Szyby Kowalski sp. z o.o.',
            'company_nip' => '779-23-45',
            'company_street' => 'ul. Głogowska 120',
            'company_postcode' => '60-243',
            'company_city' => 'Poznań',
            'category' => 'windscreen',
            'tags' => ['heated', 'sensor'],
            'delivery' => ['pickup', 'courier'],
            'condition' => 'new',
            'available_from' => now()->format('Y-m-d'),
            'is_published' => true,
            'short_description' => null,
            'description' => '<p>Szyba czołowa z czujnikiem deszczu, <strong>oryginalna</strong>.</p>',
            'attachments' => [],
        ];
    }

    public static function nipError(): string
    {
        return 'Wpisz 10 cyfr NIP bez kresek i spacji.';
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

    private static function contactSection(): Section
    {
        return self::grid(Section::make('Dane kontaktowe'))
            ->description('Imię i nazwisko oraz e-mail to para krótkich pól w jednym wierszu.')
            ->headerActions([
                Action::make('restoreContact')
                    ->label('Przywróć dane')
                    ->link()
                    ->action(fn () => DemoNotification::success('Przywrócono dane kontaktowe')),
            ])
            ->schema([
                TextInput::make('first_name')->label('Imię')->required()->columnSpan(self::span(6)),
                TextInput::make('last_name')->label('Nazwisko')->required()->columnSpan(self::span(6)),
                TextInput::make('email')->label('E-mail')->email()->required()->columnSpan(self::span(8)),
                TextInput::make('phone')
                    ->label('Telefon')
                    ->tel()
                    ->helperText('Z numerem kierunkowym, jeśli spoza Polski.')
                    ->columnSpan(self::span(4)),
            ]);
    }

    /** The section's only field is named like the section, so its label is hidden. */
    private static function accountTypeSection(): Section
    {
        return Section::make('Typ konta')
            ->schema([
                Radio::make('account_type')
                    ->label('Typ konta')
                    ->hiddenLabel()
                    ->options(DemoUsers::ACCOUNT_TYPES)
                    ->inline()
                    ->live()
                    ->required(),
            ]);
    }

    /** Appears only for a company account (`live()` + `visible()`); the NIP shows the error state. */
    private static function companySection(): Section
    {
        return self::grid(Section::make('Dane firmy'))
            ->visible(fn (Get $get): bool => $get('account_type') === 'company')
            ->schema([
                TextInput::make('company_name')->label('Nazwa firmy')->required()->columnSpanFull(),
                TextInput::make('company_nip')
                    ->label('NIP')
                    ->required()
                    ->regex(self::NIP_PATTERN)
                    ->validationMessages(['regex' => self::nipError()])
                    ->columnSpan(self::span(4)),
            ]);
    }

    /** One address, one section. */
    private static function companyAddressSection(): Section
    {
        return self::grid(Section::make('Adres firmy'))
            ->visible(fn (Get $get): bool => $get('account_type') === 'company')
            ->schema([
                TextInput::make('company_street')->label('Ulica i numer')->columnSpanFull(),
                TextInput::make('company_postcode')->label('Kod pocztowy')->placeholder('00-000')->columnSpan(self::span(3)),
                TextInput::make('company_city')->label('Miejscowość')->columnSpan(self::span(9)),
            ]);
    }

    private static function offerSection(): Section
    {
        return self::grid(Section::make('Oferta'))
            ->description('Pola wyboru, daty i przełączniki w jednej sekcji.')
            ->schema([
                Select::make('category')
                    ->label('Kategoria')
                    ->options([
                        'windscreen' => 'Szyba czołowa',
                        'rear' => 'Szyba tylna',
                        'side' => 'Szyba boczna',
                        'sunroof' => 'Szyberdach',
                    ])
                    ->required()
                    ->columnSpan(self::span(6)),
                Select::make('tags')
                    ->label('Cechy')
                    ->multiple()
                    ->options([
                        'heated' => 'Podgrzewana',
                        'sensor' => 'Czujnik deszczu',
                        'hud' => 'Wyświetlacz HUD',
                        'acoustic' => 'Akustyczna',
                        'tinted' => 'Przyciemniana',
                    ])
                    ->columnSpan(self::span(6)),
                CheckboxList::make('delivery')
                    ->label('Sposób dostawy')
                    ->options([
                        'pickup' => 'Odbiór osobisty',
                        'courier' => 'Kurier',
                        'pallet' => 'Paleta',
                        'own' => 'Transport własny',
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),
                Radio::make('condition')
                    ->label('Stan')
                    ->options(['new' => 'Nowa', 'used' => 'Używana', 'damaged' => 'Uszkodzona'])
                    ->columnSpan(self::span(6)),
                DatePicker::make('available_from')
                    ->label('Dostępna od')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->columnSpan(self::span(4)),
                Toggle::make('is_published')
                    ->label('Opublikowana')
                    ->helperText('Widoczna dla klientów w sklepie.')
                    ->columnSpanFull(),
                Textarea::make('short_description')
                    ->label('Krótki opis')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    /** The section's only field is named like the section, so its label is hidden. */
    private static function descriptionSection(): Section
    {
        return Section::make('Opis')
            ->schema([
                RichEditor::make('description')->label('Opis')->hiddenLabel(),
            ]);
    }

    /**
     * Images as previews in FilePond's grid: 3 per row on wide screens, 2 on tablets, 1 on
     * phones (`forms/file-upload.css`). Tiles are 4:3 landscape: FilePond takes the item panel
     * ratio as height / width, so 0.75. Files are never stored: the page validates and never
     * calls getState().
     */
    private static function attachmentsSection(): Section
    {
        return Section::make('Załączniki')
            ->description('Zdjęcia szyby, do 6 plików. Podglądy układają się w siatce.')
            ->schema([
                FileUpload::make('attachments')
                    ->label('Załączniki')
                    ->hiddenLabel()
                    ->multiple()
                    ->image()
                    ->panelLayout('grid')
                    ->itemPanelAspectRatio(0.75)
                    ->maxFiles(6),
            ]);
    }
}
