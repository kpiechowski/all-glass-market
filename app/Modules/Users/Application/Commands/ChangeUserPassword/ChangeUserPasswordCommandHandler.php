<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\ChangeUserPassword;

use App\Core\Contracts\Authorizer;
use App\Core\Domain\Actor;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

class ChangeUserPasswordCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private Authorizer $authorizer,
    ) {}

    #[CommandHandler]
    public function handle(ChangeUserPasswordCommand $command): User
    {
        $user = $this->userRepository->findOrFail($command->id);

        // Changing one's own password needs no Permission; ticket 06 rebuilds this handler.
        if ((int) $command->actorId !== (int) $user->getKey()) {
            $this->authorizer->ensure(Actor::user((int) $command->actorId), UsersPermission::ManageUsers);
        }

        return $this->userRepository->update($user, [
            'password' => $command->password,
        ]);
    }
}
