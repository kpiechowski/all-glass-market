<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Schemas;

use App\Core\UserInterface\Filament\Pages\Styleguide\Tables\DemoUsers;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Template C: one column of sheet sections, one entry per row, "—" for every empty value.
 * Status is a state badge with an indicator; account type and role are neutral.
 */
final class StyleguideInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->state(DemoUsers::shownRecord())
            ->columns(1)
            ->components([
                self::accountSection(),
                self::companySection(),
                self::notesSection(),
            ]);
    }

    private static function accountSection(): Section
    {
        return Section::make('Konto')
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                TextEntry::make('name')->label('Imię i nazwisko'),
                TextEntry::make('email')->label('E-mail')->copyable()->copyMessage('Skopiowano adres'),
                TextEntry::make('phone')->label('Telefon')->placeholder('—'),
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
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => DemoUsers::STATUSES[$state]['color'])
                    ->formatStateUsing(fn (string $state): string => DemoUsers::STATUSES[$state]['label'])
                    ->helperText('Wymaga zmiany hasła przy następnym logowaniu.')
                    ->icon(Heroicon::OutlinedKey)
                    ->iconColor('warning'),
                TextEntry::make('joined_at')->label('Data dołączenia')->date('d.m.Y'),
                TextEntry::make('updated_at')->label('Ostatnia zmiana')->since()->dateTooltip('d.m.Y H:i'),
            ]);
    }

    private static function companySection(): Section
    {
        return Section::make('Firma')
            ->extraAttributes(['class' => 'infolist--sheet'])
            ->schema([
                TextEntry::make('company_name')->label('Nazwa firmy'),
                TextEntry::make('company_nip')->label('NIP')->copyable(),
                TextEntry::make('company_address')->label('Adres')->placeholder('—'),
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
}
