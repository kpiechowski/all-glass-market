<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\ValueObjects;

use Carbon\CarbonImmutable;

/** One entry of the queue's failed-job store, as the Audit module sees it (FR-18). Not a Change entry. */
final readonly class FailedJob
{
    public function __construct(
        public string $id,
        public string $connection,
        public string $queue,
        public string $jobClass,
        public CarbonImmutable $failedAt,
        public string $exception,
    ) {}
}
