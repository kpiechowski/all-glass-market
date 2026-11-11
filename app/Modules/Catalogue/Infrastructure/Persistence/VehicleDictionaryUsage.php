<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence;

use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;
use App\Modules\Catalogue\Domain\Contracts\DictionaryValueUsage;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;

/** FR-24: a make or body value some vehicle has cannot be deleted, only withdrawn. */
final class VehicleDictionaryUsage implements DictionaryValueUsage
{
    public function usesOf(Dictionary $dictionary, int $valueId): array
    {
        $column = match ($dictionary) {
            Dictionary::VehicleMake => 'make_id',
            Dictionary::VehicleBody => 'body_id',
            default => null,
        };

        if ($column === null) {
            return [];
        }

        $count = VehicleModel::query()->where($column, $valueId)->count();

        return $count > 0 ? [new DictionaryUse('catalogue::uses.vehicles', $count)] : [];
    }
}
