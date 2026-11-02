<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\RequestPasswordReset;

use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\ModuleApi\EmailSending;
use App\Modules\Users\Application\Services\PasswordLinkEligibility;
use App\Modules\Users\Domain\Contracts\PasswordLinks;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * RULE-14: the answer is the same whether or not a link went out, so this
 * handler never reveals anything: an unknown e-mail, a User who may not use
 * the panel (seam: Deactivated, ticket 07), no e-mail sending, or a failed
 * send all end silently. A Guest has no Permission, so only the system may
 * send this on the Guest's behalf.
 */
final class RequestPasswordResetCommandHandler
{
    public function __construct(
        private UserRepository $users,
        private PasswordLinkEligibility $eligibility,
        private EmailSending $emailSending,
        private PasswordLinks $links,
    ) {}

    #[CommandHandler]
    public function handle(RequestPasswordResetCommand $command): void
    {
        if (! $command->actor->isSystem()) {
            throw new AuthorizationException;
        }

        if (! $this->emailSending->isAvailable()) {
            return;
        }

        $user = $this->users->findByEmail($command->email);

        if ($user === null || ! $this->eligibility->allows($user)) {
            return;
        }

        try {
            $this->links->send((int) $user->id(), PasswordLinkKind::Reset);
        } catch (UserRefused) {
            // Reported by the adapter; the Guest gets the usual neutral response.
        }
    }
}
