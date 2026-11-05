<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Commands\RestoreChange;

use App\Core\Contracts\Authorizer;
use App\Core\Contracts\CommandContext;
use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Domain\Contracts\RecordLoader;
use App\Modules\Audit\Domain\Enums\AuditPermission;
use App\Modules\Audit\Domain\Exceptions\ChangeRefused;
use App\Modules\Audit\Domain\Repositories\ChangeRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * WF-02, RULE-05. Hands the entry's raw old values to the owning module and
 * lets it carry them out as its own command; no comparison with the current
 * state (D-16). The module's command runs nested in this one and emits its
 * RecordChanged when its own frame closes, so the source entry travels to that
 * entry through the command context. A refusal by the module propagates and
 * rolls this command's transaction back: nothing changes.
 */
final class RestoreChangeCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private ChangeRepository $changes,
        private AuditableRecords $records,
        private RecordLoader $loader,
        private CommandContext $context,
    ) {}

    #[CommandHandler]
    public function handle(RestoreChangeCommand $command): void
    {
        $this->authorizer->ensure($command->actor, AuditPermission::RestoreChanges);

        $entry = $this->changes->find($command->changeId) ?? throw ChangeRefused::restoreNotFound();
        $type = $this->records->findByKey($entry->recordType());

        if ($type === null || ! $type->offersRestore()) {
            throw ChangeRefused::restoreNotOffered();
        }

        $values = $type->restorableValues($entry->changes());

        if ($entry->kind() !== ChangeKind::Changed || $values === []) {
            throw ChangeRefused::restoreNothingRestorable();
        }

        $record = $this->loader->find($type->modelClass, $entry->recordId());

        if ($record === null || (method_exists($record, 'trashed') && $record->trashed())) {
            throw ChangeRefused::restoreRecordGone();
        }

        $this->context->with(
            RestoreChangeCommand::CONTEXT_KEY,
            $entry->id(),
            fn () => $type->restorer->restore($entry->recordId(), $values, $command->actor),
        );
    }
}
