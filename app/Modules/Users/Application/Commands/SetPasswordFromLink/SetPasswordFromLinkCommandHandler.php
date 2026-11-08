<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\SetPasswordFromLink;

use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Services\PasswordLinkEligibility;
use App\Modules\Users\Domain\Contracts\PasswordLinks;
use App\Modules\Users\Domain\Enums\PasswordChangeReason;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Domain\Events\PasswordChanged;
use App\Modules\Users\Domain\Events\PasswordResetCompleted;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\Repositories\UserRepository;
use App\Modules\Users\Domain\ValueObjects\PlainPassword;
use Ecotone\Modelling\Attribute\CommandHandler;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * RULE-14, RULE-15. The link is the proof of who is asking, so the handler
 * redeems it itself: nothing but a valid, unused link changes a password
 * here. Only the system acts for the Guest. The link is used up only when
 * the password is acceptable and the User may enter the panel (a transaction
 * rolls the token back otherwise). The caller signs the User in.
 */
final class SetPasswordFromLinkCommandHandler
{
    public function __construct(
        private UserRepository $users,
        private PasswordLinks $links,
        private PasswordLinkEligibility $eligibility,
        private Hasher $hasher,
    ) {}

    #[CommandHandler]
    public function handle(SetPasswordFromLinkCommand $command): int
    {
        if (! $command->actor->isSystem()) {
            throw new AuthorizationException;
        }

        $kind = PasswordLinkKind::from($command->kind);
        $password = PlainPassword::from($command->password);

        $userId = $this->links->redeem($command->email, $command->token, $kind) ?? throw UserRefused::passwordLinkInvalid();
        $user = $this->users->get($userId);

        if (! $this->eligibility->allows($user)) {
            throw UserRefused::passwordLinkNotAllowed();
        }

        $user->setOwnPassword($this->hasher->make($password->value()));
        $this->users->save($user);

        event(new PasswordChanged($userId, ($kind === PasswordLinkKind::Invitation ? PasswordChangeReason::Invitation : PasswordChangeReason::Reset)->value));

        if ($kind === PasswordLinkKind::Reset) {
            event(new PasswordResetCompleted($userId));
        }

        return $userId;
    }
}
