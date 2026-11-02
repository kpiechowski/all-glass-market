<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\GrantSuperAdmin;

use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Exceptions\RoleAssignmentRefused;
use App\Modules\Users\Domain\Policies\SuperAdminRolePolicy;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

final class GrantSuperAdminCommandHandler
{
    public function __construct(private UserRepository $users) {}

    #[CommandHandler]
    public function handle(GrantSuperAdminCommand $command): void
    {
        if (! SuperAdminRolePolicy::canBeChangedBy($command->actor)) {
            throw RoleAssignmentRefused::superAdminConsoleOnly();
        }

        $user = $this->users->findByEmail($command->email) ?? throw RoleAssignmentRefused::userNotFound();

        if ($user->roleKey() === UserRole::SuperAdmin->value) {
            throw RoleAssignmentRefused::alreadySuperAdmin();
        }

        $user->assignRole(UserRole::SuperAdmin->value);
        $this->users->save($user);
    }
}
