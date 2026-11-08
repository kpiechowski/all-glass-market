<?php

declare(strict_types=1);

namespace App\Core\Domain\Events;

/**
 * What happened to a record. Restored means brought back from a soft delete.
 */
enum ChangeKind: string
{
    case Created = 'created';
    case Changed = 'changed';
    case Deleted = 'deleted';
    case Restored = 'restored';
}
