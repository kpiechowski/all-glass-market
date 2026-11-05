<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Commands\CleanUpExpiredChanges;

use App\Core\Domain\Actor;

/** Nightly retention cleanup of the Change history (WF-03, FR-20). Only the System may send it. */
final readonly class CleanUpExpiredChangesCommand
{
    public function __construct(public Actor $actor) {}
}
