<?php

declare(strict_types=1);

namespace App\Modules\Offers\Infrastructure\Persistence;

use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;
use App\Modules\Catalogue\Domain\Contracts\DictionaryValueUsage;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;

/** FR-25: a producer any Offer names is in use, the Trash included; it can only be withdrawn. */
final class OfferProducerUsage implements DictionaryValueUsage
{
    public function usesOf(Dictionary $dictionary, int $valueId): array
    {
        if ($dictionary !== Dictionary::Producer) {
            return [];
        }

        $count = OfferModel::query()->where('producer_id', $valueId)->count();

        return $count > 0 ? [new DictionaryUse('offers::uses.offers', $count)] : [];
    }
}
