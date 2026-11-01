<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/** WF-06, FR-28: a User began acting as another. The audit log records it. */
final readonly class ImpersonationStarted
{
    use Dispatchable;

    public function __construct(
        public int $impersonatorId,
        public int $targetUserId,
        public CarbonImmutable $occurredAt,
    ) {}
}
