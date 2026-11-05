<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Repositories;

use App\Modules\Audit\Domain\Entities\Change;

/**
 * Change entries are immutable (RULE-04): the port can only add. Retention
 * (ticket 07) adds its own removal method.
 */
interface ChangeRepository
{
    public function add(Change $change): int;

    public function find(int $id): ?Change;
}
