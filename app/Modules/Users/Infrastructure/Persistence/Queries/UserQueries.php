<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence\Queries;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Database\Eloquent\Builder;

final class UserQueries
{
    /**
     * The user list (FR-22). Tickets 06 and 07 narrow it with their markers.
     *
     * @return Builder<UserModel>
     */
    public function listing(): Builder
    {
        return UserModel::query()->orderByDesc('created_at');
    }
}
