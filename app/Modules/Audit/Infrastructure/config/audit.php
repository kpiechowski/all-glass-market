<?php

declare(strict_types=1);

return [
    /*
     * Days a Change entry is kept, counted from its time (FR-20). Temporary
     * home of the Settings value; see Domain\Contracts\RetentionPeriod.
     */
    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),
];
