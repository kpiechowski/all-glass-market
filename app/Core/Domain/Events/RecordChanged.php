<?php

declare(strict_types=1);

namespace App\Core\Domain\Events;

use App\Core\Domain\Actor;

/**
 * One recorded change of a model: its kind, field changes, relation changes
 * and the Actor of the command being handled. Emitted synchronously inside the
 * command's transaction, so a listener that throws rolls the command back.
 */
final readonly class RecordChanged
{
    /**
     * @param  class-string  $modelClass
     * @param  array<string, array{old: mixed, new: mixed}>  $changes  raw values per field
     * @param  array<string, array{added: list<int|string>, removed: list<int|string>}>  $relationChanges  raw ids per tracked relation
     * @param  array<string, mixed>  $context  what the command context carried when the frame closed (see CommandContext)
     */
    public function __construct(
        public string $modelClass,
        public int $id,
        public ChangeKind $kind,
        public array $changes,
        public array $relationChanges,
        public Actor $actor,
        public array $context = [],
    ) {}

    /** @param  array<string, mixed>  $context */
    public function withContext(array $context): self
    {
        return new self($this->modelClass, $this->id, $this->kind, $this->changes, $this->relationChanges, $this->actor, $context);
    }
}
