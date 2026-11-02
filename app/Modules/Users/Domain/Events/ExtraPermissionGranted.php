<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/** FR-06: a Super Admin granted a Permission to one User. The audit log records it. */
final readonly class ExtraPermissionGranted
{
    use Dispatchable;

    public function __construct(
        public int $userId,
        public string $permission,
        public ?int $actorId,
        public CarbonImmutable $occurredAt,
    ) {}
}
