<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\AssignUserRole;

use App\Modules\Users\Application\Policies\UserPolicy;
use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

class AssignUserRoleCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPolicy $userPolicy,
    ) {}

    #[CommandHandler]
    public function handle(AssignUserRoleCommand $command): User
    {
        $this->userPolicy->assertCanAssignRole($command->actorId);

        $user = $this->userRepository->findOrFail($command->userId);

        // Direct assignment bypasses mass-assignment guard intentionally;
        // role is managed exclusively through this command.
        $user->role = $command->role;
        $user->save();

        return $user->refresh();
    }
}
