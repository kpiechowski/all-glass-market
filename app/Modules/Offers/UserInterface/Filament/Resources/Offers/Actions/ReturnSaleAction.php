<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\ReturnSale\ReturnSaleCommand;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;

/** FR-19, template E (form): an admin returns the Sale with a required reason; the Offer is Active again. */
final class ReturnSaleAction
{
    public static function make(): Action
    {
        return Action::make('returnSale')
            ->label(__('offers::resources.offer.actions.return_sale.label'))
            ->color('gray')
            ->modalHeading(__('offers::resources.offer.actions.return_sale.heading'))
            ->modalDescription(__('offers::resources.offer.actions.return_sale.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.return_sale.label'))
            ->schema([
                Textarea::make('reason')
                    ->label(__('offers::resources.offer.fields.return_reason'))
                    ->required()
                    ->maxLength(2000)
                    ->rows(3),
            ])
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canReturnSale($record))
            ->action(function (OfferModel $record, array $data): void {
                CommandSender::send(new ReturnSaleCommand(CurrentActor::get(), (int) $record->getKey(), (string) $data['reason']));
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.return_sale.done'));
    }
}
