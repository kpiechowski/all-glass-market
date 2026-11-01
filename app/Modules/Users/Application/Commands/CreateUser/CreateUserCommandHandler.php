<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\CreateUser;

use App\Core\Contracts\Authorizer;
use App\Modules\Users\Application\ModuleApi\EmailSending;
use App\Modules\Users\Application\Services\UserManagementGuard;
use App\Modules\Users\Domain\Contracts\PasswordLinks;
use App\Modules\Users\Domain\Entities\User;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Events\ExtraPermissionGranted;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\Repositories\UserRepository;
use App\Modules\Users\Domain\ValueObjects\Email;
use App\Modules\Users\Domain\ValueObjects\PlainPassword;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;

final class CreateUserCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private UserManagementGuard $guard,
        private UserRepository $users,
        private Hasher $hasher,
        private PasswordLinks $links,
        private EmailSending $emailSending,
    ) {}

    #[CommandHandler]
    public function handle(CreateUserCommand $command): int
    {
        $this->authorizer->ensure($command->actor, UsersPermission::ManageUsers);

        $email = Email::from($command->email);
        $this->guard->ensureRoleAssignable($command->actor, $command->role);
        $this->guard->ensureCanChangeExtraPermissions($command->actor, $command->extraPermissions, []);

        if ($this->users->emailIsTaken($email->value())) {
            throw UserRefused::emailTaken();
        }

        if ($command->sendInvitation && ! $this->emailSending->isAvailable()) {
            throw UserRefused::emailSendingUnavailable();
        }

        // An invited User gets an unusable password until they set their own.
        $user = User::create($command->name, $email, $command->role, $this->hasher->make(
            $command->sendInvitation ? Str::random(64) : PlainPassword::from((string) $command->password)->value(),
        ));
        $user->updateData(
            $command->name, $email, $command->phone, $command->companyName, $command->companyNip,
            $command->companyAddress, $command->pickupAddress, $command->city, $command->postalCode,
        );
        $user->changeShippingText($command->shippingText);

        $changes = $user->changeExtraPermissions($command->extraPermissions);

        if (! $command->sendInvitation) {
            $user->requirePasswordChange();
        }

        $id = $this->users->add($user);

        if ($command->sendInvitation) {
            $this->links->send($id, PasswordLinkKind::Invitation);
        }

        foreach ($changes['granted'] as $permission) {
            event(new ExtraPermissionGranted($id, $permission, $command->actor->id, CarbonImmutable::now()));
        }

        return $id;
    }
}
