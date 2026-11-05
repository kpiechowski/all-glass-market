<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Listeners;

use App\Core\Domain\Events\RecordChanged;
use App\Modules\Audit\Application\Commands\RecordChange\RecordChangeCommand;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use Ecotone\Modelling\CommandBus;

/**
 * FR-01: every RecordChanged of a registered type becomes one Change entry.
 * Core already delivers one event per record per command, so nothing is merged
 * here. The listener runs synchronously inside the outer command's
 * transaction and is never deferred: when the entry cannot be stored the
 * exception reaches that command and rolls it back (RULE-02, D-39).
 * Change entries are written by an adapter whose model has no observer, so
 * recording never produces a RecordChanged of its own.
 */
final class RecordChangeInHistory
{
    public function __construct(
        private AuditableRecords $records,
        private CommandBus $commandBus,
    ) {}

    public function handle(RecordChanged $event): void
    {
        if (! $this->records->isRegistered($event->modelClass)) {
            return;
        }

        $this->commandBus->send(new RecordChangeCommand(
            $event->actor,
            $event->modelClass,
            $event->id,
            $event->kind->value,
            $event->changes,
            $event->relationChanges,
        ));
    }
}
