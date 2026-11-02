<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\ChangeUserPassword;

use App\Core\Contracts\Authorizer;
use App\Modules\Users\Application\Services\UserManagementGuard;
use App\Modules\Users\Domain\Enums\PasswordChangeReason;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Events\PasswordChanged;
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

        // Changing one's own password needs no Permission and clears the marker (RULE-15).
        $isSelf = $command->actor->id === $user->id();

        if (! $isSelf) {
            $this->authorizer->ensure($command->actor, UsersPermission::ManageUsers);
            $this->guard->ensureCanEditAccount($command->actor, $user);
        }

        $hash = $this->hasher->make(PlainPassword::from($command->password)->value());

        // FR-25, FR-36: an Admin's password change sets "password change required".
        $isSelf ? $user->setOwnPassword($hash) : $user->setPasswordByAdmin($hash);
        $this->users->save($user);

        event(new PasswordChanged((int) $user->id(), ($isSelf ? PasswordChangeReason::Own : PasswordChangeReason::ByAdmin)->value));
    }
}
