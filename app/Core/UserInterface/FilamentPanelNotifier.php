<?php

declare(strict_types=1);

namespace App\Core\UserInterface;

use App\Core\Contracts\PanelNotifier;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Sends to the database notifications of the panel's bell. Each recipient is
 * resolved through the auth provider, so Core knows no module's model.
 */
final class FilamentPanelNotifier implements PanelNotifier
{
    public function notify(array $userIds, string $title, string $body, string $status = 'info', array $actions = []): void
    {
        $notification = Notification::make()
            ->status($status)
            ->title($title)
            ->body($body === '' ? null : e($body))
            ->actions(array_map(
                fn (array $action): Action => Action::make('action_'.md5($action['label'].$action['url']))
                    ->label($action['label'])
                    ->url($action['url'])
                    ->button()
                    ->markAsRead($action['markAsRead'] ?? false),
                $actions,
            ));

        $provider = auth()->getProvider();

        foreach (array_unique($userIds) as $userId) {
            $recipient = $provider->retrieveById($userId);

            if ($recipient instanceof Authenticatable) {
                $notification->sendToDatabase($recipient);
            }
        }
    }
}
