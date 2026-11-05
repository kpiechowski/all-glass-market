<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Contracts;

/**
 * How long Change entries are kept (FR-20): one period for the whole history.
 * Seam for the Settings module, which will replace the config-backed adapter.
 */
interface RetentionPeriod
{
    /** @return positive-int number of days counted from each entry's time */
    public function days(): int;
}
