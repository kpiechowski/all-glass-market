<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Commands\RecordChange;

use App\Core\Domain\Actor;

/**
 * Carries one RecordChanged into Audit. The actor is the event's own, so
 * the entry belongs to whoever ran the original command (RULE-03).
 */
final readonly class RecordChangeCommand
{
    /**
     * @param  class-string  $modelClass
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @param  array<string, array{added: list<int|string>, removed: list<int|string>}>  $relationChanges
     */
    public function __construct(
        public Actor $actor,
        public string $modelClass,
        public int $recordId,
        public string $kind,
        public array $changes,
        public array $relationChanges,
    ) {}
}
