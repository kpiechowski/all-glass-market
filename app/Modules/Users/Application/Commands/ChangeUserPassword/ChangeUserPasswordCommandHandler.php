<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\ChangeUserPassword;

use App\Core\Contracts\Authorizer;
use App\Modules\Users\Application\Services\UserManagementGuard;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\Repositories\UserRepository;
use App\Modules\Users\Domain\ValueObjects\PlainPassword;
use Ecotone\Modelling\Attribute\CommandHandler;
use Illuminate\Contracts\Hashing\Hasher;

final class ChangeUserPasswordCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private UserManagementGuard $guard,
        private UserRepository $users,
        private Hasher $hasher,
    ) {}

    #[CommandHandler]
    public function handle(ChangeUserPasswordCommand $command): void
    {
        $user = $this->users->find($command->userId) ?? throw UserRefused::userNotFound();

        // Changing one's own password needs no Permission; ticket 06 rebuilds this handler.
        if ($command->actor->id !== $user->id()) {
            $this->authorizer->ensure($command->actor, UsersPermission::ManageUsers);
            $this->guard->ensureCanManage($command->actor, $user);
        }

        $user->changePasswordHash($this->hasher->make(PlainPassword::from($command->password)->value()));
        $this->users->save($user);
    }
}
