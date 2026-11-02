<?php

declare(strict_types=1);

namespace App\Core\UserInterface;

use App\Core\Contracts\PanelNotifier;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\DatabaseNotification;

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
            ->body($body === '' ? null : nl2br(e($body)))
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

    public function retract(string $url): void
    {
        // The SQL only narrows the candidates (LIKE escaping differs per driver); PHP decides.
        $segment = str_replace('\\', '', basename((string) parse_url($url, PHP_URL_PATH)));

        DatabaseNotification::query()
            ->when($segment !== '', fn ($query) => $query->where('data', 'like', '%'.$segment.'%'))
            ->each(function (DatabaseNotification $notification) use ($url): void {
                $links = collect($notification->data['actions'] ?? [])->pluck('url');

                if ($links->contains($url)) {
                    $notification->delete();
                }
            });
    }
}
