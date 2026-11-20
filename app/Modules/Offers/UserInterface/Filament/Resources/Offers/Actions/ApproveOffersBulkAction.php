<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\ApproveOffers\ApproveOffersCommand;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

/** FR-11, RULE-14: approves the selected Offers under the same rules as the single action and reports how many it skipped. */
final class ApproveOffersBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('approveSelected')
            ->label(__('offers::resources.offer.actions.approve_selected.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(__('offers::resources.offer.actions.approve_selected.heading'))
            ->modalDescription(__('offers::resources.offer.actions.approve_selected.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.approve_selected.label'))
            ->visible(fn (): bool => CurrentActor::can(OffersPermission::ManageAll))
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                /** @var array{approved: int, skipped: int} $result */
                $result = CommandSender::send(new ApproveOffersCommand(
                    CurrentActor::get(),
                    $records->map(fn ($record): int => (int) $record->getKey())->values()->all(),
                ));

                $notification = Notification::make()
                    ->title(__('offers::resources.offer.actions.approve_selected.done', $result));

                ($result['skipped'] > 0 ? $notification->warning() : $notification->success())
                    ->body($result['skipped'] > 0 ? __('offers::resources.offer.actions.approve_selected.skipped_reason') : null)
                    ->send();
            });
    }
}
