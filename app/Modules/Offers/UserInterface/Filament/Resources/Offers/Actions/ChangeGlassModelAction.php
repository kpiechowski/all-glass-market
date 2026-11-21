<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\GlassModelPickerSlideOver;
use App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\PickerVariant;
use App\Modules\Offers\Application\Commands\ChangeGlassModelOfOffers\ChangeGlassModelOfOffersCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * FR-14, D-44, template E (slide-over): an admin changes the Glass model of one Offer in any status through
 * Catalogue's picker, prefilled from the model or hint the Offer has. The status stays; the owner is told.
 */
final class ChangeGlassModelAction
{
    public static function make(): Action
    {
        return GlassModelPickerSlideOver::configure(
            Action::make('changeGlassModel')
                ->label(__('offers::resources.offer.actions.change_glass_model.label'))
                ->color('gray'),
            search: fn (OfferModel $record): ?string => $record->identification_hint,
            variant: PickerVariant::Admin,
        )
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.change_glass_model.submit'))
            ->fillForm(fn (OfferModel $record): array => [GlassModelPickerSlideOver::STATE_KEY => $record->glass_model_id])
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canChangeGlassModel($record))
            ->action(function (OfferModel $record, array $data): void {
                /** @var array{done: int, skipped: int} $result */
                $result = CommandSender::send(new ChangeGlassModelOfOffersCommand(
                    CurrentActor::get(),
                    [(int) $record->getKey()],
                    (int) $data[GlassModelPickerSlideOver::STATE_KEY],
                ));

                $notification = Notification::make();

                ($result['done'] > 0
                    ? $notification->success()->title(__('offers::resources.offer.actions.change_glass_model.done'))
                    : $notification->warning()->title(__('offers::resources.offer.actions.change_glass_model.unchanged'))
                )->send();
            });
    }
}
