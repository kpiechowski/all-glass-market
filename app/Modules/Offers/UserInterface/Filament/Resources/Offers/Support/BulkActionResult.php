<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support;

use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

/** RULE-14: what every bulk action tells the user: how many Offers it changed and how many it skipped. */
final class BulkActionResult
{
    /**
     * @param  Collection<int, mixed>  $records
     * @return list<int>
     */
    public static function ids(Collection $records): array
    {
        return $records->map(fn ($record): int => (int) $record->getKey())->values()->all();
    }

    /**
     * @param  string  $action  the key under `offers::resources.offer.actions`
     * @param  array{done: int, skipped: int}  $result
     */
    public static function notify(string $action, array $result): void
    {
        $notification = Notification::make()
            ->title(__("offers::resources.offer.actions.{$action}.done", $result));

        ($result['skipped'] > 0 ? $notification->warning() : $notification->success())
            ->body($result['skipped'] > 0 ? __("offers::resources.offer.actions.{$action}.skipped_reason") : null)
            ->send();
    }
}
