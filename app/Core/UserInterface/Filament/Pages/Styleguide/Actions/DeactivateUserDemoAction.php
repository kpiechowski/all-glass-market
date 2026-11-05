<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

/** Template E, dangerous confirmation: a red menu item, full red only on the confirm button. Demo only. */
final class DeactivateUserDemoAction
{
    public static function make(string $name = 'deactivateUser'): Action
    {
        return Action::make($name)
            ->label('Dezaktywuj')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Dezaktywować użytkownika?')
            ->modalDescription('Użytkownik nie zaloguje się, dopóki go nie aktywujesz.')
            ->modalSubmitActionLabel('Dezaktywuj')
            ->action(fn () => DemoNotification::success('Dezaktywowano użytkownika'));
    }
}
