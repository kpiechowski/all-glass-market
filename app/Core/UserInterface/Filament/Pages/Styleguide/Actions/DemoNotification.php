<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Actions;

use Filament\Notifications\Notification;

/** Every Styleguide action ends here: it says what would have happened and writes nothing (D-33). */
final class DemoNotification
{
    public static function success(string $title): void
    {
        Notification::make()
            ->success()
            ->title($title)
            ->body('Demo: nic nie zostało zapisane.')
            ->send();
    }
}
