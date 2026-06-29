<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Repositories;

use App\Core\Abstracts\ModelRepository;
use App\Modules\Audit\Domain\Models\UserJournal;
use Illuminate\Database\Eloquent\Builder;

class UserJournalRepository extends ModelRepository
{
    protected function getModel(): string
    {
        return UserJournal::class;
    }

    public function forUser(int $userId): Builder
    {
        return $this->query()->where('user_id', $userId);
    }

    public function unread(int $userId): Builder
    {
        return $this->forUser($userId)->where('is_read', false);
    }

    public function markRead(int $id): void
    {
        $this->query()->where('id', $id)->update(['is_read' => true]);
    }
}
