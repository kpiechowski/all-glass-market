<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Persistence\Queries;

use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use Illuminate\Database\Eloquent\Builder;

/** The only way the UI reads Change entries (tickets 04 and 05 build on it). */
final class ChangeQueries
{
    /**
     * One record's history, newest first (FR-09).
     *
     * @param  string  $recordType  the registration's type key
     * @return Builder<ChangeModel>
     */
    public function forRecord(string $recordType, int $recordId): Builder
    {
        return $this->newestFirst()
            ->where('record_type', $recordType)
            ->where('record_id', $recordId);
    }

    /**
     * Every entry of every module, newest first (FR-12).
     *
     * @return Builder<ChangeModel>
     */
    public function all(): Builder
    {
        return $this->newestFirst();
    }

    /** @return Builder<ChangeModel> */
    private function newestFirst(): Builder
    {
        return ChangeModel::query()->orderByDesc('occurred_at')->orderByDesc('id');
    }
}
