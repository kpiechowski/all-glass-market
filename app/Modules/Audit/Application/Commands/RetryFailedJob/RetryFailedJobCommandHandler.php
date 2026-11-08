<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Commands\RetryFailedJob;

use App\Core\Contracts\Authorizer;
use App\Modules\Audit\Domain\Contracts\FailedJobs;
use App\Modules\Audit\Domain\Enums\AuditPermission;
use App\Modules\Audit\Domain\Exceptions\FailedJobNotFound;
use Ecotone\Modelling\Attribute\CommandHandler;

/** Puts the failed job back on its queue (WF-04). Failed jobs are not Change entries: no RecordChanged. */
final class RetryFailedJobCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private FailedJobs $failedJobs,
    ) {}

    #[CommandHandler]
    public function handle(RetryFailedJobCommand $command): void
    {
        $this->authorizer->ensure($command->actor, AuditPermission::TechnicalLogs);

        if (! $this->failedJobs->retry($command->failedJobId)) {
            throw FailedJobNotFound::make();
        }
    }
}
