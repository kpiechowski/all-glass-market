<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Queries\TechnicalLog;

use App\Modules\Audit\Domain\Contracts\FailedJobs;
use App\Modules\Audit\Domain\ValueObjects\FailedJob;
use Illuminate\Support\Str;

/** The Nieudane zadania tab's rows, newest failure first (FR-18). */
final class ListFailedJobs
{
    public function __construct(private FailedJobs $failedJobs) {}

    /** @return list<FailedJobEntry> */
    public function all(): array
    {
        return array_map(fn (FailedJob $job): FailedJobEntry => new FailedJobEntry(
            id: $job->id,
            name: class_basename($job->jobClass),
            jobClass: $job->jobClass,
            queue: "{$job->connection}:{$job->queue}",
            time: $job->failedAt->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            error: Str::of($job->exception)->before("\n")->toString(),
            exception: $job->exception,
        ), $this->failedJobs->all());
    }
}
