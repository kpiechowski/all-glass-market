<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence\Queries;

use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * The only way the UI reads Journal entries, and every read is scoped to one
 * owner (RULE-12): there is no unscoped entry query.
 */
final class JournalQueries
{
    /**
     * The owner's own entries, newest first (FR-30).
     *
     * @return Builder<JournalEntryModel>
     */
    public function ownedBy(int $userId): Builder
    {
        return JournalEntryModel::query()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function unreadCountFor(int $userId): int
    {
        return $this->ownedBy($userId)->where('is_read', false)->count();
    }

    /**
     * The kinds present in the owner's Journal, for the kind filter.
     *
     * @return list<string>
     */
    public function kindsOf(int $userId): array
    {
        return JournalEntryModel::query()
            ->where('user_id', $userId)
            ->distinct()
            ->orderBy('kind')
            ->pluck('kind')
            ->all();
    }
}
