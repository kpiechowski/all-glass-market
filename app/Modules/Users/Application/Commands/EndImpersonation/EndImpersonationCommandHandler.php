<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\EndImpersonation;

use App\Modules\Users\Domain\Events\ImpersonationEnded;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * Ending needs no Permission: the impersonator may have lost it meanwhile and
 * must still get out. It only announces the end for the audit log.
 */
final class EndImpersonationCommandHandler
{
    #[CommandHandler]
    public function handle(EndImpersonationCommand $command): void
    {
        event(new ImpersonationEnded(
            (int) $command->actor->id,
            $command->targetUserId,
            $command->reason,
            CarbonImmutable::now(),
        ));
    }
}
