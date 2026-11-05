<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Persistence\Queries;

use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
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

    /**
     * The Users who made entries on one record, id to name (the Actor filter).
     *
     * @return array<string, string>
     */
    public function actorsOf(string $recordType, int $recordId): array
    {
        $ids = $this->forRecord($recordType, $recordId)->whereNotNull('actor_id')->reorder()->distinct()->pluck('actor_id');

        return UserModel::query()->whereIn('id', $ids)->orderBy('name')->pluck('name', 'id')
            ->mapWithKeys(fn (string $name, int $id): array => [(string) $id => $name])->all();
    }

    public function hasSystemEntries(string $recordType, int $recordId): bool
    {
        return $this->forRecord($recordType, $recordId)->whereNull('actor_id')->exists();
    }

    /**
     * The Users who made any entry, id to name (the central page's Actor filter).
     *
     * @return array<string, string>
     */
    public function allActors(): array
    {
        $ids = $this->all()->whereNotNull('actor_id')->reorder()->distinct()->pluck('actor_id');

        return UserModel::query()->whereIn('id', $ids)->orderBy('name')->pluck('name', 'id')
            ->mapWithKeys(fn (string $name, int $id): array => [(string) $id => $name])->all();
    }

    public function anySystemEntries(): bool
    {
        return $this->all()->whereNull('actor_id')->exists();
    }

    /** @return Builder<ChangeModel> */
    private function newestFirst(): Builder
    {
        return ChangeModel::query()->orderByDesc('occurred_at')->orderByDesc('id');
    }
}
