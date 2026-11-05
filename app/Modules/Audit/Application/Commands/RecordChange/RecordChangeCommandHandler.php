<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Commands\RecordChange;

use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Domain\Contracts\RecordLoader;
use App\Modules\Audit\Domain\Entities\Change;
use App\Modules\Audit\Domain\Repositories\ChangeRepository;
use DateTimeImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * WF-01. Turns one change of a registered record into exactly one Change
 * entry, or into none when nothing audited changed (FR-02). Any module's
 * write may be recorded, so there is no Permission check: the entry is made on
 * behalf of whoever acted, a seller included. A failure here fails the
 * command that is recording, since both run in one transaction (RULE-02).
 */
final class RecordChangeCommandHandler
{
    public function __construct(
        private AuditableRecords $records,
        private RecordLoader $loader,
        private ChangeRepository $changes,
    ) {}

    /** @return int|null the entry's id, null when the type is unregistered or nothing audited changed */
    #[CommandHandler]
    public function handle(RecordChangeCommand $command): ?int
    {
        $type = $this->records->find($command->modelClass);

        if ($type === null) {
            return null;
        }

        $kind = ChangeKind::from($command->kind);

        $changes = array_filter($command->changes, fn (string $field): bool => $type->isAudited($field), ARRAY_FILTER_USE_KEY);
        $relationChanges = array_filter($command->relationChanges, fn (string $relation): bool => $type->isTracked($relation), ARRAY_FILTER_USE_KEY);

        if ($kind === ChangeKind::Changed && $changes === [] && $relationChanges === []) {
            return null;
        }

        $title = null;

        if ($kind === ChangeKind::Deleted) {
            $record = $this->loader->find($command->modelClass, $command->recordId);
            $title = $record === null ? null : $type->titleOf($record);
        }

        return $this->changes->add(Change::record(
            $type->key,
            $command->recordId,
            $kind,
            $title,
            $changes,
            $relationChanges,
            $command->actor->id,
            $command->actor->reason,
            new DateTimeImmutable,
        ));
    }
}
