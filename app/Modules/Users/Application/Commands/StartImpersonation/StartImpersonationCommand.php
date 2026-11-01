<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\StartImpersonation;

use App\Core\Domain\Actor;

/**
 * WF-06. `alreadyImpersonating` is the session fact the UI knows: the actor
 * is the signed-in User, which during an impersonation is the target.
 */
final readonly class StartImpersonationCommand
{
    public function __construct(
        public Actor $actor,
        public int $targetUserId,
        public bool $alreadyImpersonating = false,
    ) {}
}
