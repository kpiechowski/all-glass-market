<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\DeleteUser;

use App\Core\Contracts\Authorizer;
use App\Core\Domain\Actor;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

class DeleteUserCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private Authorizer $authorizer,
    ) {}

    #[CommandHandler]
    public function handle(DeleteUserCommand $command): bool
    {
        $user = $this->userRepository->findOrFail($command->id);

        $this->authorizer->ensure(Actor::user((int) $command->actorId), UsersPermission::ManageUsers);

        return $this->userRepository->delete($user);
    }
}
