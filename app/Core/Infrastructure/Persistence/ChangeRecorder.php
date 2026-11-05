<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Persistence;

use App\Core\Domain\Events\ChangeKind;
use App\Core\Domain\Events\RecordChanged;
use App\Core\Infrastructure\Bus\ActorStack;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds RecordChanged for observers and the relation-sync helper and buffers
 * it in the current command's frame on the ActorStack. Call it from the
 * created, updated, deleted and restored observer methods, where dirty values
 * are still available. The frame coalesces what one command did to a record
 * into one event and dispatches it when the command finishes successfully,
 * synchronously and inside the command's transaction.
 */
final class ChangeRecorder
{
    public function __construct(private readonly ActorStack $actors) {}

    public function record(Model $model, ChangeKind $kind): void
    {
        $changes = [];

        if ($kind === ChangeKind::Created || $kind === ChangeKind::Changed) {
            if ($kind === ChangeKind::Changed && $this->isRestoreSave($model)) {
                return;
            }

            foreach ($model->getDirty() as $field => $new) {
                $changes[$field] = ['old' => $model->getOriginal($field), 'new' => $new];
            }
        }

        $this->actors->buffer(new RecordChanged($model::class, (int) $model->getKey(), $kind, $changes, [], $this->actors->current()));
    }

    /**
     * @param  array<string, array{added: list<int|string>, removed: list<int|string>}>  $relationChanges
     */
    public function recordRelations(Model $model, array $relationChanges): void
    {
        $this->actors->buffer(new RecordChanged($model::class, (int) $model->getKey(), ChangeKind::Changed, [], $relationChanges, $this->actors->current()));
    }

    /**
     * Restoring saves the model before it fires `restored`. That save is
     * recorded once, as Restored, not also as Changed.
     */
    private function isRestoreSave(Model $model): bool
    {
        return $model->isDirty('deleted_at')
            && $model->getAttribute('deleted_at') === null
            && $model->getOriginal('deleted_at') !== null;
    }
}
