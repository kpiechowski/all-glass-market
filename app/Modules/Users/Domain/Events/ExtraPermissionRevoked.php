<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/** FR-06: a Super Admin took an Extra Permission away from one User. The audit log records it. */
final readonly class ExtraPermissionRevoked
{
    use Dispatchable;

    public function __construct(
        public int $userId,
        public string $permission,
        public ?int $actorId,
        public CarbonImmutable $occurredAt,
    ) {}
}
