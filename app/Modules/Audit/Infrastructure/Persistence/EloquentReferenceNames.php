<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Persistence;

use App\Modules\Audit\Domain\Contracts\ReferenceNames;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EloquentReferenceNames implements ReferenceNames
{
    public function find(string $modelClass, string $nameAttribute, int|string $id): ?array
    {
        $query = $modelClass::query();
        $softDeletes = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);

        if ($softDeletes) {
            $query->withTrashed();
        }

        $record = $query->find($id);

        if ($record === null) {
            return null;
        }

        return [
            'name' => (string) $record->getAttribute($nameAttribute),
            'removed' => $softDeletes && $record->trashed(),
        ];
    }
}
