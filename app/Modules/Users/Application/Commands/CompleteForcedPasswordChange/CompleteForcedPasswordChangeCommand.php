<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\CompleteForcedPasswordChange;

use App\Core\Domain\Actor;

/** WF-07: the User sets the new password that "password change required" asks for. */
final readonly class CompleteForcedPasswordChangeCommand
{
    public function __construct(
        public Actor $actor,
        public string $password,
    ) {}
}
