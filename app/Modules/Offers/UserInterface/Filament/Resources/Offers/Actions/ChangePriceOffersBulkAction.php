<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Actions;

use App\Core\UserInterface\CommandSender;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Offers\Application\Commands\ChangePriceOfOffers\ChangePriceOfOffersCommand;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Enums\PriceChangeKind;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\BulkActionResult;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;

/**
 * FR-29, RULE-14, D-53, template E (short form): changes the price of the selected Offers by an amount, by a
 * percent or to a fixed price. Reserved, Sold and Offers whose price would not stay above zero are skipped
 * and counted; the status never changes.
 */
final class ChangePriceOffersBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('changePriceSelected')
            ->label(__('offers::resources.offer.actions.change_price_selected.label'))
            ->color('gray')
            ->modalHeading(__('offers::resources.offer.actions.change_price_selected.heading'))
            ->modalDescription(__('offers::resources.offer.actions.change_price_selected.description'))
            ->modalSubmitActionLabel(__('offers::resources.offer.actions.change_price_selected.submit'))
            ->schema([
                Select::make('kind')
                    ->label(__('offers::resources.offer.fields.price_change_kind'))
                    ->options([
                        PriceChangeKind::Amount->value => __('offers::resources.offer.price_change_kinds.amount'),
                        PriceChangeKind::Percent->value => __('offers::resources.offer.price_change_kinds.percent'),
                        PriceChangeKind::Fixed->value => __('offers::resources.offer.price_change_kinds.fixed'),
                    ])
                    ->default(PriceChangeKind::Amount->value)
                    ->native(false)
                    ->selectablePlaceholder(false)
                    ->live()
                    ->required(),
                TextInput::make('value')
                    ->label(__('offers::resources.offer.fields.price_change_value'))
                    ->helperText(fn (Get $get): string => __('offers::resources.offer.helpers.price_change_'.$get('kind')))
                    ->suffix(fn (Get $get): string => $get('kind') === PriceChangeKind::Percent->value ? '%' : 'zł')
                    ->numeric()
                    ->step(0.01)
                    ->minValue(fn (Get $get): ?int => $get('kind') === PriceChangeKind::Fixed->value ? 1 : null)
                    ->required(),
            ])
            ->visible(fn (): bool => CurrentActor::can(OffersPermission::Create))
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, array $data): void {
                BulkActionResult::notify('change_price_selected', CommandSender::send(new ChangePriceOfOffersCommand(
                    CurrentActor::get(),
                    BulkActionResult::ids($records),
                    (string) $data['kind'],
                    (int) round((float) $data['value'] * 100),
                )));
            });
    }
}
