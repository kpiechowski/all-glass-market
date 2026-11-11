<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/** §9, WF-04: a Glass model left picking and search; adverts of its offers come down. */
final readonly class GlassModelWithdrawn
{
    use Dispatchable;

    public function __construct(
        public int $glassModelId,
        public CarbonImmutable $occurredAt,
    ) {}
}
