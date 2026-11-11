<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/** §9, WF-04: a withdrawn Glass model is offered again. */
final readonly class GlassModelRestored
{
    use Dispatchable;

    public function __construct(
        public int $glassModelId,
        public CarbonImmutable $occurredAt,
    ) {}
}
