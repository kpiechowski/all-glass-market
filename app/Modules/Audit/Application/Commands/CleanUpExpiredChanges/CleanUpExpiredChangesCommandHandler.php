<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Commands\CleanUpExpiredChanges;

use App\Core\Exceptions\AuthorizationException;
use App\Modules\Audit\Domain\Contracts\RetentionPeriod;
use App\Modules\Audit\Domain\Repositories\ChangeRepository;
use DateTimeImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * WF-03 step 1. Retention is the only way entries go (RULE-04), so no User
 * may trigger it: there is no Permission for it, only the System runs it.
 */
final class CleanUpExpiredChangesCommandHandler
{
    public function __construct(
        private RetentionPeriod $period,
        private ChangeRepository $changes,
    ) {}

    /** @return int number of entries removed */
    #[CommandHandler]
    public function handle(CleanUpExpiredChangesCommand $command): int
    {
        if (! $command->actor->isSystem()) {
            throw new AuthorizationException;
        }

        $cutoff = (new DateTimeImmutable)->modify('-'.$this->period->days().' days');

        return $this->changes->removeOlderThan($cutoff);
    }
}
