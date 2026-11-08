<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

use App\Core\Abstracts\EloquentRepository;

/** @extends EloquentRepository<Widget> */
final class RecordedWidgetRepository extends EloquentRepository
{
    public function __construct()
    {
        parent::__construct(RecordedWidgetModel::class, new WidgetMapper);
    }

    /** @param list<int> $tagIds */
    public function syncTags(int $id, array $tagIds): void
    {
        $this->syncRelation($id, 'tags', $tagIds);
    }
}
