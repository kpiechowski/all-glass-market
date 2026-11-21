<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\SellOffer\SellOfferCommand;
use App\Modules\Offers\Application\ModuleApi\BuyersPrices;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferActionVisibility;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferFormData;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

/** FR-18, template E (short form): sell with a channel and a price, prefilled with the buyer's price and editable. */
final class SellOfferAction
{
    public static function make(): Action
    {
        return Action::make('sell')
            ->label(__('offers::resources.offer.actions.sell.label'))
            ->color('gray')
            ->modalHeading(__('offers::resources.offer.actions.sell.heading'))
            ->modalDescription(__('offers::resources.offer.actions.sell.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.sell.label'))
            ->fillForm(fn (OfferModel $record): array => [
                'channel' => SaleChannel::Panel->value,
                'price' => self::prefilledPrice($record),
            ])
            ->schema([
                Select::make('channel')
                    ->label(__('offers::resources.offer.fields.sale_channel'))
                    ->options(SaleChannel::class)
                    ->required(),
                TextInput::make('price')
                    ->label(__('offers::resources.offer.fields.sale_price'))
                    ->helperText(__('offers::resources.offer.helpers.sale_price'))
                    ->numeric()
                    ->minValue(0.01)
                    ->required()
                    ->suffix('zł'),
            ])
            ->visible(fn (OfferModel $record): bool => OfferActionVisibility::canSell($record))
            ->action(function (OfferModel $record, array $data): void {
                CommandSender::send(new SellOfferCommand(
                    CurrentActor::get(),
                    (int) $record->getKey(),
                    $data['channel'] instanceof SaleChannel ? $data['channel']->value : (string) $data['channel'],
                    OfferFormData::grosze($data['price']) ?? 0,
                ));
            })
            ->successNotificationTitle(__('offers::resources.offer.actions.sell.done'));
    }

    /** The buyer's price in złoty, or the entered price while the owner's Account type is unknown. */
    private static function prefilledPrice(OfferModel $record): ?string
    {
        $grosze = app(BuyersPrices::class)->forOffer($record->owner_id, $record->price_grosze) ?? $record->price_grosze;

        return $grosze === null ? null : number_format($grosze / 100, 2, '.', '');
    }
}
