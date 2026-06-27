<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\ChangeUserPassword;

use App\Modules\Users\Application\Policies\UserPolicy;
use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

class ChangeUserPasswordCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPolicy $userPolicy,
    ) {}

    #[CommandHandler]
    public function handle(ChangeUserPasswordCommand $command): User
    {
        $user = $this->userRepository->findOrFail($command->id);

        $this->userPolicy->assertCanChangePassword($command->actorId, $user);

        return $this->userRepository->update($user, [
            'password' => $command->password,
        ]);
    }
}
