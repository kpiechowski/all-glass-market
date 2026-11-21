<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\WithdrawOffer\WithdrawOfferCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;

/** FR-24, template E: the owner or an admin withdraws a Pending, Rejected or Active Offer; it leaves the channels. */
final class WithdrawOfferAction
{
    public static function make(): Action
    {
        return Action::make('withdraw')
            ->label(__('offers::resources.offer.actions.withdraw.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(__('offers::resources.offer.actions.withdraw.heading'))
            ->modalDescription(__('offers::resources.offer.actions.withdraw.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.withdraw.label'))
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canWithdraw($record))
            ->action(function (OfferModel $record): void {
                CommandSender::send(new WithdrawOfferCommand(CurrentActor::get(), (int) $record->getKey()));
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.withdraw.done'));
    }
}
