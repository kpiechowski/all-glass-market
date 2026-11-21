<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\ReserveOffer\ReserveOfferCommand;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;

/** FR-16, template E (short form): reserve an Active Offer with a free-text "for whom" note. */
final class ReserveOfferAction
{
    public static function make(): Action
    {
        return Action::make('reserve')
            ->label(__('offers::resources.offer.actions.reserve.label'))
            ->color('gray')
            ->modalHeading(__('offers::resources.offer.actions.reserve.heading'))
            ->modalDescription(__('offers::resources.offer.actions.reserve.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.reserve.label'))
            ->schema([
                Textarea::make('note')
                    ->label(__('offers::resources.offer.fields.reservation_note'))
                    ->helperText(__('offers::resources.offer.helpers.reservation_note'))
                    ->required()
                    ->maxLength(OfferDomainPolicy::MAX_RESERVATION_NOTE_LENGTH)
                    ->rows(3),
            ])
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canReserve($record))
            ->action(function (OfferModel $record, array $data): void {
                CommandSender::send(new ReserveOfferCommand(CurrentActor::get(), (int) $record->getKey(), (string) $data['note']));
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.reserve.done'));
    }
}
