<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\RevokeSuperAdmin;

use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Exceptions\RoleAssignmentRefused;
use App\Modules\Users\Domain\Policies\SuperAdminRolePolicy;
use App\Modules\Users\Domain\Repositories\UserRoles;
use Ecotone\Modelling\Attribute\CommandHandler;

final class RevokeSuperAdminCommandHandler
{
    public function __construct(private UserRoles $userRoles) {}

    #[CommandHandler]
    public function handle(RevokeSuperAdminCommand $command): void
    {
        if (! SuperAdminRolePolicy::canBeChangedBy($command->actor)) {
            throw RoleAssignmentRefused::superAdminConsoleOnly();
        }

        $userId = $this->userRoles->userIdByEmail($command->email) ?? throw RoleAssignmentRefused::userNotFound();

        if ($this->userRoles->roleKeyOf($userId) !== UserRole::SuperAdmin->value) {
            throw RoleAssignmentRefused::notSuperAdmin();
        }

        $this->userRoles->assign($userId, UserRole::User->value);
    }
}
