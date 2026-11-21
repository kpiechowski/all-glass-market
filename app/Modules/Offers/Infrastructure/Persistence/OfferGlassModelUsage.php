<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence;

use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;
use App\Modules\Catalogue\Domain\Contracts\GlassModelUsage;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;

/** FR-25: a Glass model any Offer refers to is in use, the Trash included; it can only be withdrawn. */
final class OfferGlassModelUsage implements GlassModelUsage
{
    public function usesOf(int $glassModelId): array
    {
        $count = OfferModel::query()->where('glass_model_id', $glassModelId)->count();

        return $count > 0 ? [new DictionaryUse('offers::uses.offers', $count)] : [];
    }
}
