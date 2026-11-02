<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\StartImpersonation;

use App\Core\Contracts\Authorizer;
use App\Modules\Users\Application\Services\UserManagementGuard;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Events\ImpersonationStarted;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** Checks RULE-10 and announces the start; the session switch itself is the UI's. */
final class StartImpersonationCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private UserManagementGuard $guard,
        private UserRepository $users,
    ) {}

    #[CommandHandler]
    public function handle(StartImpersonationCommand $command): void
    {
        $this->authorizer->ensure($command->actor, UsersPermission::Impersonate);

        $target = $this->users->find($command->targetUserId) ?? throw UserRefused::userNotFound();

        $this->guard->ensureCanImpersonate($command->actor, $target, $command->alreadyImpersonating);

        event(new ImpersonationStarted((int) $command->actor->id, $command->targetUserId, CarbonImmutable::now()));
    }
}
