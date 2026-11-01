<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Persistence;

use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Repositories\UserRoles;

final class EloquentUserRoles implements UserRoles
{
    public function roleKeyOf(int $userId): ?string
    {
        $role = User::query()->whereKey($userId)->value('role');

        return $role === null ? null : (string) $role;
    }

    public function assign(int $userId, string $roleKey): void
    {
        User::query()->whereKey($userId)->update(['role' => $roleKey]);
    }

    public function userIdByEmail(string $email): ?int
    {
        $id = User::query()->where('email', $email)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function userIdsWithRoles(array $roleKeys): array
    {
        return User::query()->whereIn('role', $roleKeys)->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }
}
