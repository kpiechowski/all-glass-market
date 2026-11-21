<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\ReleaseReservation\ReleaseReservationCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;

/** FR-17, template E: release the Reservation; hidden from the seller over an admin's Reservation (RULE-05). */
final class ReleaseReservationAction
{
    public static function make(): Action
    {
        return Action::make('releaseReservation')
            ->label(__('offers::resources.offer.actions.release.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(__('offers::resources.offer.actions.release.heading'))
            ->modalDescription(__('offers::resources.offer.actions.release.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.release.label'))
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canRelease($record))
            ->action(function (OfferModel $record): void {
                CommandSender::send(new ReleaseReservationCommand(CurrentActor::get(), (int) $record->getKey()));
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.release.done'));
    }
}
