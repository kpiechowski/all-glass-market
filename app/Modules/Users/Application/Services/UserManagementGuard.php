<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Services;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Domain\Entities\User;
use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Exceptions\RoleAssignmentRefused;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\Policies\UserManagementPolicy;
use App\Modules\Users\Domain\Repositories\UserRepository;

/**
 * Gathers the facts RULE-08 needs (ranks from the Role order) and asks the
 * domain policy. Shared by the handlers that manage other Users.
 */
final class UserManagementGuard
{
    public function __construct(
        private RoleRegistry $registry,
        private UserRepository $users,
    ) {}

    public function ensureCanManage(Actor $actor, User $target): void
    {
        if (! UserManagementPolicy::canManage($this->rankOfActor($actor), $this->rankOfRole($target->roleKey()))) {
            throw UserRefused::cannotManage();
        }
    }

    /** A Role may be given to a new User (FR-24). */
    public function ensureRoleAssignable(Actor $actor, string $roleKey): void
    {
        if (! UserManagementPolicy::canAssignRole($roleKey, $this->registry->rankOf($roleKey), $this->rankOfActor($actor))) {
            throw UserRefused::roleNotAssignable();
        }
    }

    /** A Role may replace the target's current one (FR-25). */
    public function ensureRoleChangeAllowed(Actor $actor, User $target, string $roleKey): void
    {
        if ($target->roleKey() === UserRole::SuperAdmin->value || $roleKey === UserRole::SuperAdmin->value) {
            throw RoleAssignmentRefused::superAdminConsoleOnly();
        }

        if (! $this->registry->isRegistered($roleKey)) {
            throw RoleAssignmentRefused::roleNotRegistered();
        }

        if (! UserManagementPolicy::canChangeRoleOf($actor->id, (int) $target->id(), $target->roleKey())) {
            throw UserRefused::cannotChangeOwnRole();
        }

        $this->ensureCanManage($actor, $target);
        $this->ensureRoleAssignable($actor, $roleKey);
    }

    private function rankOfActor(Actor $actor): int
    {
        if ($actor->isSystem()) {
            return PHP_INT_MAX;
        }

        $roleKey = $this->users->roleKeyOf((int) $actor->id);

        return $roleKey === null ? 0 : $this->rankOfRole($roleKey);
    }

    /** An unregistered Role ranks lowest, so an Admin can still repair that User. */
    private function rankOfRole(string $roleKey): int
    {
        return $this->registry->rankOf($roleKey) ?? 0;
    }
}
