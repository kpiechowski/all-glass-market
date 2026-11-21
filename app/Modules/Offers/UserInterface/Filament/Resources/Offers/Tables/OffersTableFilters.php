<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Tables;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\ModuleApi\CatalogueDictionaries;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelCandidates;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummaries;
use App\Modules\Offers\Application\ModuleApi\BuyersPrices;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Infrastructure\Persistence\Queries\OfferQueries;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support\OfferFormData;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * FR-31: the filters of the Offers list. The glass ones (text, position, colour) ask Catalogue for the
 * matching Glass model ids through its module interface; the price range is on the buyer's price.
 * The "glass_model" filter is the target of the "N ofert" link: `?filters[glass_model][glass_model_id]=<id>`.
 */
final class OffersTableFilters
{
    /** @return array<mixed> */
    public static function make(): array
    {
        return [
            self::glassTextFilter(),
            self::dictionaryFilter(
                'position',
                fn (): array => app(CatalogueDictionaries::class)->positions(),
                fn (int $id): array => app(GlassModelCandidates::class)->matchingIds('', positionId: $id, includeWithdrawn: true),
            ),
            self::dictionaryFilter(
                'colour',
                fn (): array => app(CatalogueDictionaries::class)->colours(),
                fn (int $id): array => app(GlassModelCandidates::class)->matchingIds('', colourId: $id, includeWithdrawn: true),
            ),
            self::withoutGlassModelFilter(),
            self::glassModelFilter(),
            self::ownerFilter(),
            self::producerFilter(),
            self::conditionFilter(),
            self::ratingFilter(),
            self::priceFilter(),
        ];
    }

    private static function glassTextFilter(): Filter
    {
        return Filter::make('glass_text')
            ->label(__('offers::resources.offer.filters.text'))
            ->schema([
                TextInput::make('text')
                    ->label(__('offers::resources.offer.filters.text'))
                    ->helperText(__('offers::resources.offer.filters.text_helper')),
            ])
            ->query(fn (Builder $query, array $data): Builder => filled($data['text'] ?? null)
                ? app(OfferQueries::class)->ofGlassModels($query, app(GlassModelCandidates::class)->matchingIds((string) $data['text'], includeWithdrawn: true))
                : $query)
            ->indicateUsing(fn (array $data): array => filled($data['text'] ?? null)
                ? [__('offers::resources.offer.filters.text_indicator', ['text' => $data['text']])]
                : []);
    }

    /**
     * @param  callable(): list<object{id: int, name: string}>  $entries
     * @param  callable(int): list<int>  $matchingGlassModelIds  Catalogue's ids for one dictionary value
     */
    private static function dictionaryFilter(string $name, callable $entries, callable $matchingGlassModelIds): Filter
    {
        return Filter::make($name)
            ->label(__("offers::resources.offer.filters.{$name}"))
            ->schema([
                Select::make('value')
                    ->label(__("offers::resources.offer.filters.{$name}"))
                    ->options(function () use ($entries): array {
                        $options = [];

                        foreach ($entries() as $entry) {
                            $options[$entry->id] = $entry->name;
                        }

                        return $options;
                    })
                    ->native(false),
            ])
            ->query(function (Builder $query, array $data) use ($matchingGlassModelIds): Builder {
                if (blank($data['value'] ?? null)) {
                    return $query;
                }

                return app(OfferQueries::class)->ofGlassModels($query, $matchingGlassModelIds((int) $data['value']));
            })
            ->indicateUsing(function (array $data) use ($entries, $name): array {
                foreach ($entries() as $entry) {
                    if ((string) $entry->id === (string) ($data['value'] ?? '')) {
                        return [__("offers::resources.offer.filters.{$name}").': '.$entry->name];
                    }
                }

                return [];
            });
    }

    private static function withoutGlassModelFilter(): Filter
    {
        return Filter::make('without_glass_model')
            ->label(__('offers::resources.offer.filters.without_glass_model'))
            ->toggle()
            ->query(fn (Builder $query): Builder => app(OfferQueries::class)->withoutGlassModel($query));
    }

    private static function glassModelFilter(): Filter
    {
        return Filter::make('glass_model')
            ->label(__('offers::resources.offer.filters.glass_model'))
            ->schema([
                TextInput::make('glass_model_id')
                    ->label(__('offers::resources.offer.filters.glass_model_id'))
                    ->numeric()
                    ->minValue(1),
            ])
            ->query(fn (Builder $query, array $data): Builder => filled($data['glass_model_id'] ?? null)
                ? app(OfferQueries::class)->ofGlassModels($query, [(int) $data['glass_model_id']])
                : $query)
            ->indicateUsing(function (array $data): array {
                if (blank($data['glass_model_id'] ?? null)) {
                    return [];
                }

                $summary = app(GlassModelSummaries::class)->of((int) $data['glass_model_id']);

                return [__('offers::resources.offer.filters.glass_model_indicator', ['name' => $summary?->mainEurocode ?? '#'.$data['glass_model_id']])];
            });
    }

    private static function ownerFilter(): SelectFilter
    {
        return SelectFilter::make('owner_id')
            ->label(__('offers::resources.offer.filters.owner'))
            ->options(fn (): array => app(OfferQueries::class)->sellersWithOffers())
            ->searchable()
            ->visible(fn (): bool => CurrentActor::can(OffersPermission::ManageAll));
    }

    private static function producerFilter(): SelectFilter
    {
        return SelectFilter::make('producer_id')
            ->label(__('offers::resources.offer.filters.producer'))
            ->options(function (): array {
                $options = [];

                foreach (app(CatalogueDictionaries::class)->producers() as $producer) {
                    $options[$producer->id] = $producer->name;
                }

                return $options;
            })
            ->searchable();
    }

    private static function conditionFilter(): SelectFilter
    {
        return SelectFilter::make('condition')
            ->label(__('offers::resources.offer.filters.condition'))
            ->options(OfferCondition::class);
    }

    private static function ratingFilter(): SelectFilter
    {
        return SelectFilter::make('rating')
            ->label(__('offers::resources.offer.filters.rating'))
            ->options(array_combine(
                range(1, 5),
                array_map(fn (int $stars): string => __('offers::resources.offer.filters.rating_option', ['stars' => $stars]), range(1, 5)),
            ))
            ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                ? app(OfferQueries::class)->withDisplayedStars($query, (int) $data['value'])
                : $query);
    }

    private static function priceFilter(): Filter
    {
        return Filter::make('buyers_price')
            ->label(__('offers::resources.offer.filters.price'))
            ->schema([
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    TextInput::make('from')->label(__('offers::resources.offer.filters.price_from'))->numeric()->minValue(0),
                    TextInput::make('to')->label(__('offers::resources.offer.filters.price_to'))->numeric()->minValue(0),
                ]),
            ])
            ->query(function (Builder $query, array $data): Builder {
                $from = OfferFormData::grosze($data['from'] ?? null);
                $to = OfferFormData::grosze($data['to'] ?? null);

                if ($from === null && $to === null) {
                    return $query;
                }

                return app(OfferQueries::class)->withBuyersPriceBetween($query, $from, $to, app(BuyersPrices::class)->vatRateBasisPoints());
            })
            ->indicateUsing(fn (array $data): array => array_values(array_filter([
                filled($data['from'] ?? null) ? __('offers::resources.offer.filters.price_indicator_from', ['price' => $data['from']]) : null,
                filled($data['to'] ?? null) ? __('offers::resources.offer.filters.price_indicator_to', ['price' => $data['to']]) : null,
            ])));
    }
}
