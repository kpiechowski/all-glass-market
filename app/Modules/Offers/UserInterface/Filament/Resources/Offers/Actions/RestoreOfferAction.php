<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\RestoreOffer\RestoreOfferCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;

/** FR-25, template E: an admin brings the Offer back from the Trash with the status it had. */
final class RestoreOfferAction
{
    public static function make(bool $primary = false): Action
    {
        return Action::make('restore')
            ->label(__('offers::resources.offer.actions.restore.label'))
            ->color($primary ? 'primary' : 'gray')
            ->requiresConfirmation()
            ->modalHeading(__('offers::resources.offer.actions.restore.heading'))
            ->modalDescription(__('offers::resources.offer.actions.restore.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.restore.label'))
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canRestore($record))
            ->action(function (OfferModel $record): void {
                CommandSender::send(new RestoreOfferCommand(CurrentActor::get(), (int) $record->getKey()));
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.restore.done'));
    }
}
