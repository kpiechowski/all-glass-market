<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence;

use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;
use App\Modules\Catalogue\Domain\Contracts\DictionaryValueUsage;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;

/** FR-24: a position, colour or equipment value used on Glass models cannot be deleted. */
final class GlassModelDictionaryValueUsage implements DictionaryValueUsage
{
    public function usesOf(Dictionary $dictionary, int $valueId): array
    {
        $count = match ($dictionary) {
            Dictionary::Position => GlassModelModel::query()->where('position_id', $valueId)->count(),
            Dictionary::Colour => GlassModelModel::query()->where('colour_id', $valueId)->count(),
            Dictionary::Equipment => GlassModelModel::query()->whereHas('equipment', fn ($query) => $query->whereKey($valueId))->count(),
            default => 0,
        };

        return $count === 0 ? [] : [new DictionaryUse('catalogue::uses.glass_models', $count)];
    }
}
