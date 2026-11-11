<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence;

use App\Modules\Catalogue\Domain\Contracts\DictionaryEntry;
use App\Modules\Catalogue\Domain\Contracts\DictionaryReader;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;

final class EloquentDictionaryReader implements DictionaryReader
{
    public function active(Dictionary $dictionary): array
    {
        return DictionaryValueModel::query()
            ->where('dictionary', $dictionary)
            ->where('withdrawn', false)
            ->orderBy('name')
            ->get()
            ->map(fn (DictionaryValueModel $value): DictionaryEntry => new DictionaryEntry($value->id, $value->name, false))
            ->all();
    }

    public function names(array $ids): array
    {
        return DictionaryValueModel::query()->whereKey($ids)->pluck('name', 'id')->all();
    }
}
