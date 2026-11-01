<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A User's password was saved as changed. `reason` is a `PasswordChangeReason`
 * value; 'forced' is the forced change of WF-07 (FR-38). Audit's listener
 * arrives with the Audit rework.
 */
final readonly class PasswordChanged
{
    use Dispatchable;

    public function __construct(
        public int $userId,
        public string $reason,
    ) {}
}
