<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Contracts;

/** Finds an audited record, soft-deleted ones included, so its title can be read. */
interface RecordLoader
{
    /**
     * @param  class-string  $modelClass
     */
    public function find(string $modelClass, int $id): ?object;
}
