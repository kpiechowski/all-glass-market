<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\SetPasswordFromLink;

use App\Core\Domain\Actor;

/**
 * WF-03, WF-04: a Guest sets a password with a reset or invitation link.
 * `kind` is 'reset' or 'invitation'. The handler returns the User's id.
 */
final readonly class SetPasswordFromLinkCommand
{
    public function __construct(
        public Actor $actor,
        public string $email,
        public string $token,
        public string $kind,
        public string $password,
    ) {}
}
