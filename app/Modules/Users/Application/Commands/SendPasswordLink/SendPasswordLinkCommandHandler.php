<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\SendPasswordLink;

use App\Core\Contracts\Authorizer;
use App\Modules\Users\Application\ModuleApi\EmailSending;
use App\Modules\Users\Application\Services\PasswordLinkEligibility;
use App\Modules\Users\Application\Services\UserManagementGuard;
use App\Modules\Users\Domain\Contracts\PasswordLinks;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

final class SendPasswordLinkCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private UserManagementGuard $guard,
        private UserRepository $users,
        private PasswordLinkEligibility $eligibility,
        private EmailSending $emailSending,
        private PasswordLinks $links,
    ) {}

    #[CommandHandler]
    public function handle(SendPasswordLinkCommand $command): void
    {
        $this->authorizer->ensure($command->actor, UsersPermission::ManageUsers);

        $kind = PasswordLinkKind::from($command->kind);
        $user = $this->users->find($command->userId) ?? throw UserRefused::userNotFound();

        $this->guard->ensureCanEditAccount($command->actor, $user);

        if (! $this->emailSending->isAvailable()) {
            throw UserRefused::emailSendingUnavailable();
        }

        if (! $this->eligibility->allows($user)) {
            throw UserRefused::passwordLinkNotAllowed();
        }

        $this->links->send($command->userId, $kind);
    }
}
