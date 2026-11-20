<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support;

use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummaries;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;

/** What a list shows for the Offer's glass: the Glass model's main Eurocode (current data, FR-15) or the seller's hint. */
final class OfferGlassLabel
{
    public static function of(OfferModel $offer): ?string
    {
        if ($offer->glass_model_id === null) {
            return $offer->identification_hint;
        }

        return app(GlassModelSummaries::class)->of($offer->glass_model_id)?->mainEurocode;
    }
}
