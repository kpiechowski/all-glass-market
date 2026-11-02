<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Services;

use App\Core\Contracts\Authorizer;
use App\Core\Contracts\Permission;
use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Domain\Repositories\UserRepository;

/**
 * FR-03, FR-06: a check passes when the User's Role was granted the Permission
 * or the User holds it as an Extra Permission. Super
 * Admin passes through the wildcard it was declared with, not through its name.
 */
final class UserAuthorizer implements Authorizer
{
    public function __construct(
        private RoleRegistry $registry,
        private UserRepository $users,
    ) {}

    public function can(Actor $actor, Permission $permission): bool
    {
        if ($actor->isSystem()) {
            return true;
        }

        $roleKey = $this->users->roleKeyOf((int) $actor->id);

        if ($roleKey === null) {
            return false;
        }

        return $this->registry->roleHolds($roleKey, $permission)
            || $this->holdsAsExtra((int) $actor->id, $permission);
    }

    /** FR-06: the second source, a Permission granted to this User alone. */
    private function holdsAsExtra(int $userId, Permission $permission): bool
    {
        return in_array((string) $permission->value, $this->users->extraPermissionsOf($userId), true);
    }

    public function ensure(Actor $actor, Permission $permission): void
    {
        if (! $this->can($actor, $permission)) {
            throw new AuthorizationException;
        }
    }
}
