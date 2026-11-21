<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Tables;

use App\Core\UserInterface\CurrentActor;
use App\Core\UserInterface\Filament\Tables\Columns\RecordCardColumn;
use App\Modules\Offers\Application\ModuleApi\BuyersPrices;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\Infrastructure\Persistence\Queries\OfferQueries;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferListGlass;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferRating;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Database\Eloquent\Builder;

/** The columns of the Offers list: the mobile card and the desktop columns, the glass and buyer's price read in one batch per page. */
final class OffersTableColumns
{
    /** @return array<mixed> */
    public static function make(): array
    {
        return [
            RecordCardColumn::make()
                ->title(fn (OfferModel $record): string => $record->number)
                ->subtitle(fn (OfferModel $record): ?string => app(OfferListGlass::class)->label($record))
                ->status(fn (OfferModel $record) => $record->status)
                ->meta(fn (OfferModel $record): array => [
                    $record->price_grosze === null ? null : number_format($record->price_grosze / 100, 2, ',', ' ').' '.__('offers::resources.offer.currency'),
                    self::formattedBuyersPrice($record),
                ]),

            TextColumn::make('number')
                ->label(__('offers::resources.offer.fields.number'))
                ->searchable()
                ->sortable()
                ->visibleFrom('md'),

            ViewColumn::make('glass')
                ->label(__('offers::resources.offer.fields.glass'))
                ->state(fn (OfferModel $record) => app(OfferListGlass::class)->of($record))
                ->view('offers::filament.tables.offer-glass-cell')
                ->visibleFrom('md'),

            TextColumn::make('producer.name')
                ->label(__('offers::resources.offer.fields.producer'))
                ->placeholder('—')
                ->toggleable(isToggledHiddenByDefault: true)
                ->visibleFrom('md'),

            TextColumn::make('condition')
                ->label(__('offers::resources.offer.fields.condition'))
                ->badge()
                ->color('gray')
                ->placeholder('—')
                ->toggleable(isToggledHiddenByDefault: true)
                ->visibleFrom('md'),

            TextColumn::make('rating')
                ->label(__('offers::resources.offer.fields.rating'))
                ->state(fn (OfferModel $record): ?string => OfferRating::stars($record))
                ->placeholder('—')
                ->toggleable(isToggledHiddenByDefault: true)
                ->visibleFrom('md'),

            TextColumn::make('price_grosze')
                ->label(__('offers::resources.offer.fields.price'))
                ->money('PLN', divideBy: 100)
                ->sortable()
                ->alignEnd()
                ->placeholder('—')
                ->visibleFrom('md'),

            TextColumn::make('buyers_price')
                ->label(__('offers::resources.offer.fields.buyers_price'))
                ->state(fn (OfferModel $record): ?int => app(BuyersPrices::class)->forOffer($record->owner_id, $record->price_grosze))
                ->money('PLN', divideBy: 100)
                ->sortable(query: fn (Builder $query, string $direction): Builder => app(OfferQueries::class)
                    ->orderedByBuyersPrice($query, $direction, app(BuyersPrices::class)->vatRateBasisPoints()))
                ->alignEnd()
                ->placeholder('—')
                ->visibleFrom('md'),

            TextColumn::make('owner.name')
                ->label(__('offers::resources.offer.fields.owner'))
                ->visible(fn (): bool => CurrentActor::can(OffersPermission::ManageAll))
                ->visibleFrom('md'),

            TextColumn::make('status')
                ->label(__('offers::resources.offer.fields.status'))
                ->badge()
                ->visibleFrom('md'),
        ];
    }

    private static function formattedBuyersPrice(OfferModel $record): ?string
    {
        $grosze = app(BuyersPrices::class)->forOffer($record->owner_id, $record->price_grosze);

        return $grosze === null ? null : __('offers::resources.offer.buyers_price_meta', ['price' => number_format($grosze / 100, 2, ',', ' ')]);
    }
}
