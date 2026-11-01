<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\SetPasswordChangeRequirement;

use App\Core\Domain\Actor;

/** FR-26: an Admin sets or clears "password change required" without touching the password. */
final readonly class SetPasswordChangeRequirementCommand
{
    public function __construct(
        public Actor $actor,
        public int $userId,
        public bool $required,
    ) {}
}
