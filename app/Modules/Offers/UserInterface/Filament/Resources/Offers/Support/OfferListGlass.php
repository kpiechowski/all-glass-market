<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support;

use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummaries;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummary;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;

/**
 * FR-31: the Glass model summaries of one page of the list, read from Catalogue in one batch (`ofMany`)
 * instead of once per row. The list primes it with its page; a row it was not primed for is read singly.
 * Scoped to the request, so the next render starts clean.
 */
final class OfferListGlass
{
    /** @var array<int, GlassModelSummary> by Glass model id */
    private array $summaries = [];

    public function __construct(private readonly GlassModelSummaries $glassModels) {}

    /** @param  iterable<OfferModel>  $offers */
    public function prime(iterable $offers): void
    {
        $ids = [];

        foreach ($offers as $offer) {
            if ($offer->glass_model_id !== null) {
                $ids[] = $offer->glass_model_id;
            }
        }

        $this->summaries = $this->glassModels->ofMany($ids);
    }

    public function of(OfferModel $offer): ?GlassModelSummary
    {
        if ($offer->glass_model_id === null) {
            return null;
        }

        return $this->summaries[$offer->glass_model_id] ??= $this->glassModels->of($offer->glass_model_id);
    }

    /** The main Eurocode (current data, FR-15) or the seller's hint when there is no Glass model. */
    public function label(OfferModel $offer): ?string
    {
        return $offer->glass_model_id === null ? $offer->identification_hint : $this->of($offer)?->mainEurocode;
    }
}
