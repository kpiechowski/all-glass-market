<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\DeleteUser;

use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

class DeleteUserCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    #[CommandHandler]
    public function handle(DeleteUserCommand $command): bool
    {
        $user = $this->userRepository->findOrFail($command->id);

        return $this->userRepository->delete($user);
    }
}
