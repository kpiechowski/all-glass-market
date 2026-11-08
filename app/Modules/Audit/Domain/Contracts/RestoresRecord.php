<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Contracts;

use App\Core\Domain\Actor;
use App\Core\Exceptions\DomainException;

/**
 * Implemented by an owning module for each record type that offers Restore
 * (FR-14). It dispatches the module's own command, so validation, events and
 * syncs run as for any edit. Audit has already checked the Permission and
 * chosen the values; the module does not need to.
 */
interface RestoresRecord
{
    /**
     * @param  array<string, mixed>  $values  field => the raw old value to write back, restorable fields only
     *
     * @throws DomainException when the module refuses; nothing is then changed
     */
    public function restore(int $recordId, array $values, Actor $actor): void;
}
