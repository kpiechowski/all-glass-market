<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\GlassModelPickerSlideOver;
use App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\PickerVariant;
use App\Modules\Offers\Application\Commands\ApproveOffer\ApproveOfferCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;

/**
 * FR-10, WF-03 step 2, template E (slide-over): approving an Offer that has only a hint opens
 * Catalogue's picker prefilled from the hint. The admin picks a model or creates one in place;
 * the chosen model is selected for the Offer and the Offer is approved in one command.
 */
final class ApproveOfferWithGlassModelAction
{
    public static function make(bool $primary = false): Action
    {
        return GlassModelPickerSlideOver::configure(
            Action::make('approveWithGlassModel')
                ->label(__('offers::resources.offer.actions.approve_with_glass_model.label'))
                ->color($primary ? 'primary' : 'gray'),
            search: fn (OfferModel $record): ?string => $record->identification_hint,
            variant: PickerVariant::Admin,
        )
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.approve_with_glass_model.submit'))
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canApproveWithGlassModel($record))
            ->action(function (OfferModel $record, array $data): void {
                CommandSender::send(new ApproveOfferCommand(
                    CurrentActor::get(),
                    (int) $record->getKey(),
                    (int) $data[GlassModelPickerSlideOver::STATE_KEY],
                ));
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.approve.done'));
    }
}
