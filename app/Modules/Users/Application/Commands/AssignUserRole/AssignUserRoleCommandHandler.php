<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\AssignUserRole;

use App\Core\Contracts\Authorizer;
use App\Modules\Users\Application\Services\UserManagementGuard;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Exceptions\RoleAssignmentRefused;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

final class AssignUserRoleCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private UserManagementGuard $guard,
        private UserRepository $users,
    ) {}

    #[CommandHandler]
    public function handle(AssignUserRoleCommand $command): void
    {
        $this->authorizer->ensure($command->actor, UsersPermission::ManageUsers);

        $user = $this->users->find($command->userId) ?? throw RoleAssignmentRefused::userNotFound();

        // RULE-08 and RULE-09: Super Admin moves only through the console, nobody changes their own Role.
        $this->guard->ensureRoleChangeAllowed($command->actor, $user, $command->role);

        $user->assignRole($command->role);
        $this->users->save($user);
    }
}
