<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Repositories;

use App\Modules\Audit\Domain\Entities\Change;
use DateTimeImmutable;

/**
 * Change entries are immutable (RULE-04): the port can only add. Retention
 * removal is the only delete path.
 */
interface ChangeRepository
{
    public function add(Change $change): int;

    public function find(int $id): ?Change;

    /**
     * Retention only (FR-20). Restore markers pointing at removed entries are
     * left as they are: restored_from_id has no foreign key.
     *
     * @return int number of entries removed
     */
    public function removeOlderThan(DateTimeImmutable $cutoff): int;
}
