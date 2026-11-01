<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * WF-06: an impersonation ended, by the return button or because the target
 * was deactivated meanwhile (WF-05). The audit log records it.
 */
final readonly class ImpersonationEnded
{
    use Dispatchable;

    public function __construct(
        public int $impersonatorId,
        public int $targetUserId,
        public string $reason,
        public CarbonImmutable $occurredAt,
    ) {}
}
