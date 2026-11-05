<?php

declare(strict_types=1);

namespace App\Modules\Audit\Infrastructure\Settings;

use App\Modules\Audit\Domain\Contracts\RetentionPeriod;

/** Reads the period from Audit's config until Settings exists (365 days by default). */
final class ConfigRetentionPeriod implements RetentionPeriod
{
    public function days(): int
    {
        return max(1, (int) config('audit.retention_days', 365));
    }
}
