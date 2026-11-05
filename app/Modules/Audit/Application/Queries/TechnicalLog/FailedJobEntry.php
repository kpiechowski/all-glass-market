<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Queries\TechnicalLog;

/** A failed background job ready to show: readable name, time, one-line error and the full exception (FR-18). */
final readonly class FailedJobEntry
{
    public function __construct(
        public string $id,
        public string $name,
        public string $jobClass,
        public string $queue,
        public string $time,
        public string $error,
        public string $exception,
    ) {}
}
