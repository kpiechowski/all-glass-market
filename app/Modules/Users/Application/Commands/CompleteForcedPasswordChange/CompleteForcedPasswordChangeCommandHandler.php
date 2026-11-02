<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\CompleteForcedPasswordChange;

use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Domain\Enums\PasswordChangeReason;
use App\Modules\Users\Domain\Events\PasswordChanged;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\Repositories\UserRepository;
use App\Modules\Users\Domain\ValueObjects\PlainPassword;
use Ecotone\Modelling\Attribute\CommandHandler;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * FR-38. Only the User themself: the current password is not asked for, so
 * nobody else may use this path. The caller ends the other sessions.
 */
final class CompleteForcedPasswordChangeCommandHandler
{
    public function __construct(
        private UserRepository $users,
        private Hasher $hasher,
    ) {}

    #[CommandHandler]
    public function handle(CompleteForcedPasswordChangeCommand $command): void
    {
        if ($command->actor->id === null) {
            throw new AuthorizationException;
        }

        $user = $this->users->find($command->actor->id) ?? throw UserRefused::userNotFound();
        $password = PlainPassword::from($command->password);

        $user->completeForcedPasswordChange(
            $this->hasher->make($password->value()),
            $this->hasher->check($password->value(), $user->passwordHash()),
        );
        $this->users->save($user);

        event(new PasswordChanged($command->actor->id, PasswordChangeReason::Forced->value));
    }
}
