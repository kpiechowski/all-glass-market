<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Commands\RemoveFailedJob;

use App\Core\Contracts\Authorizer;
use App\Modules\Audit\Domain\Contracts\FailedJobs;
use App\Modules\Audit\Domain\Enums\AuditPermission;
use App\Modules\Audit\Domain\Exceptions\FailedJobNotFound;
use Ecotone\Modelling\Attribute\CommandHandler;

/** Forgets the failed job without running it (WF-04). The Change history is untouched. Failed jobs are not Change entries: no RecordChanged. */
final class RemoveFailedJobCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private FailedJobs $failedJobs,
    ) {}

    #[CommandHandler]
    public function handle(RemoveFailedJobCommand $command): void
    {
        $this->authorizer->ensure($command->actor, AuditPermission::TechnicalLogs);

        if (! $this->failedJobs->forget($command->failedJobId)) {
            throw FailedJobNotFound::make();
        }
    }
}
