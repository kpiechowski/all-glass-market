<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Services;

use App\Modules\Users\Domain\Enums\PermissionEnum;
use App\Modules\Users\Domain\Enums\RoleEnum;
use App\Modules\Users\Domain\Exceptions\PermissionDeniedException;
use App\Modules\Users\Domain\Models\User;

class PermissionService
{
    public function hasPermission(User $user, PermissionEnum $permission): bool
    {
        // Root can do anything — early return, no config lookup needed
        if ($user->role === RoleEnum::Root) {
            return true;
        }

        $rolePermissions = config('users.permissions.'.($user->role?->value ?? ''), []);

        if ($rolePermissions === '*') {
            return true;
        }

        return is_array($rolePermissions) && in_array($permission->value, $rolePermissions, strict: true);
    }

    public function assertPermission(User $user, PermissionEnum $permission): void
    {
        if (! $this->hasPermission($user, $permission)) {
            throw new PermissionDeniedException($permission->value);
        }
    }
}
