<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\CreateUser;

use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

class CreateUserCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    #[CommandHandler]
    public function handle(CreateUserCommand $command): User
    {
        return $this->userRepository->create([
            'name' => $command->name,
            'email' => $command->email,
            'password' => $command->password,
        ]);
    }
}
