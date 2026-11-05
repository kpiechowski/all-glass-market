<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * WF-03: a User set a new password with a reset link. Not fired for an
 * invitation link. The framework's PasswordReset event does not fire here, as
 * the broker's callback saves nothing.
 */
final readonly class PasswordResetCompleted
{
    use Dispatchable;

    public function __construct(
        public int $userId,
    ) {}
}
