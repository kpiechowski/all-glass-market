<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Contracts;

use App\Modules\Audit\Domain\Entities\Change;

/** Reads a record's stored Change entries in the order they were made. */
interface RecordChangeReader
{
    /**
     * The `Changed` entries made after the latest entry that changed `$field`, oldest first. Null when
     * no entry ever changed that field. The entry that changed the field is not part of the answer.
     *
     * @return list<Change>|null
     */
    public function after(string $recordType, int $recordId, string $field): ?array;
}
