<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Services;

use App\Modules\Audit\Domain\Models\ChangesLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ChangesLogService
{
    public function createCreatedEntry(Model $model): void
    {
        $changes = $this->getRawChanges($model);
        $this->log($model, $changes, 'Użytkownik dodał nowy rekord.', 'created');
    }

    public function createUpdatedEntry(Model $model): void
    {
        if (! $model->isDirty()) {
            return;
        }

        $changes = $this->getRawChanges($model);

        if (empty($changes)) {
            return;
        }

        $count = count($changes);
        $fieldWord = $count === 1 ? 'polu' : 'polach';
        $message = "Użytkownik wprowadził zmiany w [{$count}] {$fieldWord}.";

        $this->log($model, $changes, $message, 'updated');
    }

    public function createDeletedEntry(Model $model): void
    {
        $this->log($model, [], 'Użytkownik usunął rekord.', 'deleted');
    }

    protected function getRawChanges(Model $model): array
    {
        $original = $model->getOriginal();
        $dirty = $model->getDirty();

        $loggable = $model->getLoggableAttributes();
        $notLoggable = $model->getNotLoggableAttributes();

        $changes = [];

        foreach ($dirty as $field => $newValue) {
            if (
                ($loggable !== ['*'] && ! in_array($field, $loggable)) ||
                in_array($field, $notLoggable)
            ) {
                continue;
            }

            $changes[$field] = [
                'old' => array_key_exists($field, $original) ? $model->getOriginal($field) : null,
                'new' => $model->getAttribute($field),
            ];
        }

        return $changes;
    }

    protected function log(Model $model, array $changes, string $message, string $action): void
    {
        ChangesLog::create([
            'user_id' => Auth::id(),
            'loggable_type' => $model::class,
            'loggable_id' => $model->getKey(),
            'changes' => $changes,
            'message' => $message,
            'meta_data' => compact('action'),
        ]);
    }
}
