<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Commands\RestoreChange;

use App\Core\Domain\Actor;

/** Writes the raw old values of one Change entry's restorable fields back, through the owning module (FR-13, FR-14). */
final readonly class RestoreChangeCommand
{
    /**
     * Key under which the source entry's id travels in the command context, to the
     * RecordChanged the module's own command emits.
     */
    public const string CONTEXT_KEY = 'audit.restored_from';

    public function __construct(
        public Actor $actor,
        public int $changeId,
    ) {}
}
