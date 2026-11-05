<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Actions;

use App\Core\UserInterface\Filament\Pages\Styleguide\Tables\DemoUsers;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/** Template E, short form: three fields in a modal. Demo only, sends a notification. */
final class InviteUserDemoAction
{
    public static function make(string $name = 'inviteUser'): Action
    {
        return Action::make($name)
            ->label('Zaproś użytkownika')
            ->icon(Heroicon::OutlinedEnvelope)
            ->modalHeading('Zaproś użytkownika')
            ->modalDescription('Wyślemy e-mail z linkiem do ustawienia hasła.')
            ->modalSubmitActionLabel('Wyślij zaproszenie')
            ->modalWidth(Width::Large)
            ->schema([
                TextInput::make('name')->label('Imię i nazwisko')->required(),
                TextInput::make('email')->label('E-mail')->email()->required(),
                Select::make('role')->label('Rola')->options(DemoUsers::ROLES)->default('customer')->required(),
            ])
            ->action(fn () => DemoNotification::success('Wysłano zaproszenie'));
    }
}
