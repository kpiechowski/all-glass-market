<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * WF-05, FR-27: a User was reactivated. Other modules react (Offers decide what
 * happens to the seller's offers); the audit log records it.
 */
final readonly class UserReactivated
{
    use Dispatchable;

    public function __construct(
        public int $userId,
        public CarbonImmutable $occurredAt,
    ) {}
}
