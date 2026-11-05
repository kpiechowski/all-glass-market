<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * WF-03: a reset link was sent to a User. Dispatched by the command handler
 * only when a link really went out; the framework's own event does not fire,
 * because the links are made with the password broker's createToken().
 */
final readonly class PasswordResetRequested
{
    use Dispatchable;

    public function __construct(
        public int $userId,
    ) {}
}
