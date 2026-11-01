<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\AssignUserRole;

use App\Core\Contracts\Authorizer;
use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Exceptions\RoleAssignmentRefused;
use App\Modules\Users\Domain\Repositories\UserRoles;
use Ecotone\Modelling\Attribute\CommandHandler;

final class AssignUserRoleCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private RoleRegistry $registry,
        private UserRoles $userRoles,
    ) {}

    #[CommandHandler]
    public function handle(AssignUserRoleCommand $command): void
    {
        $this->authorizer->ensure($command->actor, UsersPermission::ManageUsers);

        $current = $this->userRoles->roleKeyOf($command->userId) ?? throw RoleAssignmentRefused::userNotFound();

        // RULE-09: Super Admin moves only through the console commands, in either direction.
        if ($command->role === UserRole::SuperAdmin->value || $current === UserRole::SuperAdmin->value) {
            throw RoleAssignmentRefused::superAdminConsoleOnly();
        }

        if (! $this->registry->isRegistered($command->role)) {
            throw RoleAssignmentRefused::roleNotRegistered();
        }

        $this->userRoles->assign($command->userId, $command->role);
    }
}
