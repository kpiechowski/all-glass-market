<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\CreateUser;

use App\Core\Contracts\Authorizer;
use App\Modules\Users\Application\Services\UserManagementGuard;
use App\Modules\Users\Domain\Entities\User;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\Repositories\UserRepository;
use App\Modules\Users\Domain\ValueObjects\Email;
use App\Modules\Users\Domain\ValueObjects\PlainPassword;
use Ecotone\Modelling\Attribute\CommandHandler;
use Illuminate\Contracts\Hashing\Hasher;

final class CreateUserCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private UserManagementGuard $guard,
        private UserRepository $users,
        private Hasher $hasher,
    ) {}

    #[CommandHandler]
    public function handle(CreateUserCommand $command): int
    {
        $this->authorizer->ensure($command->actor, UsersPermission::ManageUsers);

        $email = Email::from($command->email);
        $password = PlainPassword::from($command->password);
        $this->guard->ensureRoleAssignable($command->actor, $command->role);

        if ($this->users->emailIsTaken($email->value())) {
            throw UserRefused::emailTaken();
        }

        $user = User::create($command->name, $email, $command->role, $this->hasher->make($password->value()));
        $user->updateData(
            $command->name, $email, $command->phone, $command->companyName, $command->companyNip,
            $command->companyAddress, $command->pickupAddress, $command->city, $command->postalCode,
        );
        $user->changeShippingText($command->shippingText);

        return $this->users->add($user);
    }
}
