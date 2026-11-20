<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\ApproveOffer\ApproveOfferCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;

/** FR-09, template E: approves an Offer that has a Glass model. Without one the admin gets `ApproveOfferWithGlassModelAction`. */
final class ApproveOfferAction
{
    public static function make(bool $primary = false): Action
    {
        return Action::make('approve')
            ->label(__('offers::resources.offer.actions.approve.label'))
            ->color($primary ? 'primary' : 'gray')
            ->requiresConfirmation()
            ->modalHeading(__('offers::resources.offer.actions.approve.heading'))
            ->modalDescription(__('offers::resources.offer.actions.approve.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.approve.label'))
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canApprove($record))
            ->action(function (OfferModel $record): void {
                CommandSender::send(new ApproveOfferCommand(CurrentActor::get(), (int) $record->getKey()));
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.approve.done'));
    }
}
