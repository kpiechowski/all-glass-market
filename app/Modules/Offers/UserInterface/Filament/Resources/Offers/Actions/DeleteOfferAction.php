<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\DeleteOffer\DeleteOfferCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;

/** FR-25, template E (dangerous): an admin moves the Offer to the Trash; it keeps its status and can be restored. */
final class DeleteOfferAction
{
    public static function make(): Action
    {
        return Action::make('delete')
            ->label(__('offers::resources.offer.actions.delete.label'))
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('offers::resources.offer.actions.delete.heading'))
            ->modalDescription(__('offers::resources.offer.actions.delete.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.delete.label'))
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canDelete($record))
            ->action(function (OfferModel $record): void {
                CommandSender::send(new DeleteOfferCommand(CurrentActor::get(), (int) $record->getKey()));
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.delete.done'));
    }
}
