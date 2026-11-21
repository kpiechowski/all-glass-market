<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\DeleteOfferPermanently\DeleteOfferPermanentlyCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;

/** FR-26, template E (dangerous): from the Trash only, removes the Offer with its photos for good; the page then leaves the removed record. */
final class DeleteOfferPermanentlyAction
{
    public static function make(): Action
    {
        return Action::make('deletePermanently')
            ->label(__('offers::resources.offer.actions.delete_permanently.label'))
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('offers::resources.offer.actions.delete_permanently.heading'))
            ->modalDescription(__('offers::resources.offer.actions.delete_permanently.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.delete_permanently.label'))
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canDeletePermanently($record))
            ->action(function (OfferModel $record): void {
                CommandSender::send(new DeleteOfferPermanentlyCommand(CurrentActor::get(), (int) $record->getKey()));
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.delete_permanently.done'))
            ->successRedirectUrl(fn (): string => OfferResource::getUrl('index'));
    }
}
