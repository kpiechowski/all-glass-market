<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Persistence;

use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Domain\Contracts\RecordChangeReader;
use App\Modules\Audit\Infrastructure\Persistence\Mappers\ChangeMapper;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Audit\Infrastructure\Persistence\Queries\ChangeQueries;

final class EloquentRecordChangeReader implements RecordChangeReader
{
    public function __construct(
        private readonly ChangeQueries $queries,
        private readonly ChangeMapper $mapper,
    ) {}

    public function after(string $recordType, int $recordId, string $field): ?array
    {
        $marker = $this->queries->forRecord($recordType, $recordId)
            ->get()
            ->first(fn (ChangeModel $entry): bool => array_key_exists($field, $entry->changes ?? []));

        if ($marker === null) {
            return null;
        }

        return $this->queries->forRecord($recordType, $recordId)
            ->reorder('id')
            ->where('id', '>', $marker->id)
            ->where('kind', ChangeKind::Changed)
            ->get()
            ->map(fn (ChangeModel $entry) => $this->mapper->toEntity($entry))
            ->values()
            ->all();
    }
}
