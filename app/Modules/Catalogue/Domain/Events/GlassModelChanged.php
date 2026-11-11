<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/** §9: on every change of a Glass model (parameters, a renamed position, colour or equipment value), never on creation. */
final readonly class GlassModelChanged
{
    use Dispatchable;

    public function __construct(
        public int $glassModelId,
        public CarbonImmutable $occurredAt,
    ) {}
}
