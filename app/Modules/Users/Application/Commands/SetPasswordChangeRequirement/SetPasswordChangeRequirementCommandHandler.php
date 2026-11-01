<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\SetPasswordChangeRequirement;

use App\Core\Contracts\Authorizer;
use App\Modules\Users\Application\Services\UserManagementGuard;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

final class SetPasswordChangeRequirementCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private UserManagementGuard $guard,
        private UserRepository $users,
    ) {}

    #[CommandHandler]
    public function handle(SetPasswordChangeRequirementCommand $command): void
    {
        $this->authorizer->ensure($command->actor, UsersPermission::ManageUsers);

        $user = $this->users->find($command->userId) ?? throw UserRefused::userNotFound();
        $this->guard->ensureCanManage($command->actor, $user);

        $command->required ? $user->requirePasswordChange() : $user->clearPasswordChangeRequirement();

        $this->users->save($user);
    }
}
