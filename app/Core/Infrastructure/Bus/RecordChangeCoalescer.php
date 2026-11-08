<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Bus;

use App\Core\Domain\Events\ChangeKind;
use App\Core\Domain\Events\RecordChanged;

/**
 * Folds the changes one command made into one RecordChanged per record.
 * A Changed folds into an earlier Created or Changed of the same record:
 * fields keep the first old and the last new, relation diffs are united.
 * Deleted and Restored are never folded and start a new event, in order.
 */
final class RecordChangeCoalescer
{
    /**
     * @param  list<RecordChanged>  $events
     * @return list<RecordChanged>
     */
    public function coalesce(array $events): array
    {
        $result = [];
        $foldTarget = [];

        foreach ($events as $event) {
            $key = $event->modelClass.'#'.$event->id;

            if ($event->kind === ChangeKind::Changed && isset($foldTarget[$key])) {
                $result[$foldTarget[$key]] = $this->fold($result[$foldTarget[$key]], $event);

                continue;
            }

            $result[] = $event;
            $foldTarget[$key] = $event->kind === ChangeKind::Deleted || $event->kind === ChangeKind::Restored
                ? null
                : array_key_last($result);

            if ($foldTarget[$key] === null) {
                unset($foldTarget[$key]);
            }
        }

        return array_values(array_filter(
            $result,
            fn (RecordChanged $event) => $event->kind !== ChangeKind::Changed
                || $event->changes !== []
                || $event->relationChanges !== [],
        ));
    }

    private function fold(RecordChanged $target, RecordChanged $later): RecordChanged
    {
        $changes = $target->changes;

        foreach ($later->changes as $field => $change) {
            $old = array_key_exists($field, $changes) ? $changes[$field]['old'] : $change['old'];

            if ($target->kind === ChangeKind::Changed && $old === $change['new']) {
                unset($changes[$field]);

                continue;
            }

            $changes[$field] = ['old' => $old, 'new' => $change['new']];
        }

        $relations = $target->relationChanges;

        foreach ($later->relationChanges as $relation => $diff) {
            $added = $relations[$relation]['added'] ?? [];
            $removed = $relations[$relation]['removed'] ?? [];

            foreach ($diff['added'] as $id) {
                if (in_array($id, $removed, true)) {
                    $removed = array_values(array_diff($removed, [$id]));
                } else {
                    $added[] = $id;
                }
            }

            foreach ($diff['removed'] as $id) {
                if (in_array($id, $added, true)) {
                    $added = array_values(array_diff($added, [$id]));
                } else {
                    $removed[] = $id;
                }
            }

            if ($added === [] && $removed === []) {
                unset($relations[$relation]);
            } else {
                $relations[$relation] = ['added' => $added, 'removed' => $removed];
            }
        }

        return new RecordChanged($target->modelClass, $target->id, $target->kind, $changes, $relations, $target->actor);
    }
}
