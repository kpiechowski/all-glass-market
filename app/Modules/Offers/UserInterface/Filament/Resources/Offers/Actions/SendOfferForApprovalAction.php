<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\SendOfferForApproval\SendOfferForApprovalCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/** FR-06, FR-13, template E: the owner sends the Offer on; a refusal names the missing fields. */
final class SendOfferForApprovalAction
{
    public static function make(): Action
    {
        return Action::make('sendForApproval')
            ->label(__('offers::resources.offer.actions.send_for_approval.label'))
            ->modalHeading(__('offers::resources.offer.actions.send_for_approval.heading'))
            ->modalDescription(__('offers::resources.offer.actions.send_for_approval.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.send_for_approval.submit'))
            ->requiresConfirmation()
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canSend($record))
            ->action(function (OfferModel $record): void {
                CommandSender::send(new SendOfferForApprovalCommand(CurrentActor::get(), (int) $record->getKey()));

                Notification::make()
                    ->success()
                    ->title(__($record->refresh()->status === OfferStatus::Active
                        ? 'offers::resources.offer.actions.send_for_approval.done_active'
                        : 'offers::resources.offer.actions.send_for_approval.done_pending'))
                    ->send();
            });
    }
}
