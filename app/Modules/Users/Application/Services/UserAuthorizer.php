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
 * FR-03: a check passes when the User's Role was granted the Permission. Super
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

        // Ticket 08 adds Extra Permissions as a second source: `|| <held as extra>`.
        return $this->registry->roleHolds($roleKey, $permission);
    }

    public function ensure(Actor $actor, Permission $permission): void
    {
        if (! $this->can($actor, $permission)) {
            throw new AuthorizationException;
        }
    }
}
