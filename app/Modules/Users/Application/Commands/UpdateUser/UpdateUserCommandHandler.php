<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\UpdateUser;

use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

class UpdateUserCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    #[CommandHandler]
    public function handle(UpdateUserCommand $command): User
    {
        $user = $this->userRepository->findOrFail($command->id);

        $attributes = [
            'name' => $command->name,
            'email' => $command->email,
        ];

        if ($command->password !== null) {
            $attributes['password'] = $command->password;
        }

        return $this->userRepository->update($user, $attributes);
    }
}
