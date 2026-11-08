<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Commands\RemoveFailedJob;

use App\Core\Domain\Actor;

/** FR-18: one failed background job, by its id in the failed-job store. */
final readonly class RemoveFailedJobCommand
{
    public function __construct(
        public Actor $actor,
        public string $failedJobId,
    ) {}
}
