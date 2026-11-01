<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\UpdateUser;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Services\UserManagementGuard;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\Repositories\UserRepository;
use App\Modules\Users\Domain\ValueObjects\Email;
use App\Modules\Users\Domain\ValueObjects\PlainPassword;
use Ecotone\Modelling\Attribute\CommandHandler;
use Illuminate\Contracts\Hashing\Hasher;

final class UpdateUserCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private UserManagementGuard $guard,
        private UserRepository $users,
        private Hasher $hasher,
    ) {}

    #[CommandHandler]
    public function handle(UpdateUserCommand $command): void
    {
        $user = $this->users->find($command->userId) ?? throw UserRefused::userNotFound();

        $isSelf = $command->actor->id === $user->id();
        $mayManage = $this->authorizer->can($command->actor, UsersPermission::ManageUsers);

        if (! $mayManage && ! $isSelf) {
            throw new AuthorizationException;
        }

        if ($mayManage) {
            $this->guard->ensureCanManage($command->actor, $user);
        }

        $email = Email::from($command->email);

        if ($this->users->emailIsTaken($email->value(), $command->userId)) {
            throw UserRefused::emailTaken();
        }

        $password = $command->password === null || $command->password === ''
            ? null
            : PlainPassword::from($command->password);

        $roleChanges = $command->role !== null && $command->role !== $user->roleKey();

        if ($roleChanges) {
            if (! $mayManage) {
                throw new AuthorizationException;
            }

            $this->guard->ensureRoleChangeAllowed($command->actor, $user, $command->role);
        }

        $user->updateData(
            $command->name, $email, $command->phone, $command->companyName, $command->companyNip,
            $command->companyAddress, $command->pickupAddress, $command->city, $command->postalCode,
        );

        // FR-15: only a manager edits the Otomoto shipping text.
        if ($mayManage) {
            $user->changeShippingText($command->shippingText);
        }

        if ($roleChanges) {
            $user->assignRole($command->role);
        }

        if ($password !== null) {
            $user->changePasswordHash($this->hasher->make($password->value()));
        }

        $this->users->save($user);
    }
}
