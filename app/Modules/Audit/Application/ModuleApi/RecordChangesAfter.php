<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\ModuleApi;

use App\Modules\Audit\Application\Queries\PresentChange\ChangePresenter;
use App\Modules\Audit\Domain\Contracts\RecordChangeReader;

/**
 * A record's Change history cut at a milestone, for a module's own screen (Offers' "changes since the
 * last approval", FR-12). The milestone is the latest entry that changed one named field of the record
 * (for an Offer `last_approved_at`): what came after it is returned as readable lines, the milestone's
 * own changes (the approval's status change) are not.
 */
final class RecordChangesAfter
{
    public function __construct(
        private readonly RecordChangeReader $changes,
        private readonly ChangePresenter $presenter,
    ) {}

    /**
     * Net per field: one line per field with the value right after the milestone as old and the latest
     * as new; a field that ended where it started is left out. Relation changes stay one line per entry.
     *
     * @param  string  $recordType  the registration's stable type key
     * @param  list<string>  $excludeFields  fields the caller does not want listed
     * @return list<PresentedLine>|null null when the field never changed (no milestone); an empty list when nothing came after it
     */
    public function lastChangeOf(string $recordType, int $recordId, string $field, array $excludeFields = []): ?array
    {
        $entries = $this->changes->after($recordType, $recordId, $field);

        if ($entries === null) {
            return null;
        }

        $net = [];
        $relationLines = [];

        foreach ($entries as $entry) {
            foreach ($entry->changes() as $changed => $change) {
                if (in_array($changed, $excludeFields, true)) {
                    continue;
                }

                $net[$changed] = [
                    'old' => array_key_exists($changed, $net) ? $net[$changed]['old'] : ($change['old'] ?? null),
                    'new' => $change['new'] ?? null,
                ];
            }

            if ($entry->relationChanges() !== []) {
                array_push($relationLines, ...$this->presenter->present($recordType, [], $entry->relationChanges()));
            }
        }

        $net = array_filter($net, fn (array $change): bool => $change['old'] !== $change['new']);

        return [...$this->presenter->present($recordType, $net, []), ...$relationLines];
    }
}
