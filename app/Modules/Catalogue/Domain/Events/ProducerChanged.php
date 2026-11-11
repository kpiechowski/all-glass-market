<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Events;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/** §9: Offers' adverts and integrations refresh; whoever reacts reads the producer's name. */
final readonly class ProducerChanged
{
    use Dispatchable;

    public function __construct(
        public int $producerId,
        public CarbonImmutable $occurredAt,
    ) {}
}
