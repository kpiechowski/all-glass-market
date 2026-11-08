<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Queue;

use App\Modules\Audit\Domain\Contracts\FailedJobs;
use App\Modules\Audit\Domain\ValueObjects\FailedJob;
use Carbon\CarbonImmutable;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Support\Facades\Artisan;

/** Laravel's failed-job provider (`queue.failed`), retried through `queue:retry` so the payload is re-queued the framework's way. */
final class LaravelFailedJobs implements FailedJobs
{
    public function __construct(private FailedJobProviderInterface $provider) {}

    public function all(): array
    {
        $jobs = array_map(fn (object $row): FailedJob => new FailedJob(
            id: (string) $row->id,
            connection: (string) $row->connection,
            queue: (string) $row->queue,
            jobClass: $this->jobClass((string) $row->payload),
            failedAt: CarbonImmutable::parse((string) $row->failed_at),
            exception: (string) $row->exception,
        ), $this->provider->all());

        usort($jobs, fn (FailedJob $a, FailedJob $b): int => $b->failedAt <=> $a->failedAt);

        return $jobs;
    }

    public function retry(string $id): bool
    {
        if ($this->provider->find($id) === null) {
            return false;
        }

        Artisan::call('queue:retry', ['id' => [$id]]);

        return true;
    }

    public function forget(string $id): bool
    {
        return $this->provider->forget($id);
    }

    private function jobClass(string $payload): string
    {
        $decoded = json_decode($payload, true);

        return (string) ($decoded['displayName'] ?? $decoded['job'] ?? 'unknown');
    }
}
