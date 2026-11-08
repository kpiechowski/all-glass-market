<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Contracts;

use App\Modules\Audit\Domain\ValueObjects\FailedJob;

/** Port over the queue's failed-job store (FR-18). Both writes return false when the job is not there (any more). */
interface FailedJobs
{
    /** @return list<FailedJob> newest first */
    public function all(): array;

    /** Puts the job back on its queue and forgets the failed entry. */
    public function retry(string $id): bool;

    /** Forgets the failed entry; the job is not run. */
    public function forget(string $id): bool;
}
