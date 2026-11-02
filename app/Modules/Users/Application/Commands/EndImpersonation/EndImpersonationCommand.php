<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\EndImpersonation;

use App\Core\Domain\Actor;

/**
 * WF-06. The actor is the impersonator, who is returning to their own account;
 * `reason` is one of the constants here.
 */
final readonly class EndImpersonationCommand
{
    public const string RETURNED = 'returned';

    public const string TARGET_DEACTIVATED = 'target_deactivated';

    public const string IMPERSONATOR_DEACTIVATED = 'impersonator_deactivated';

    public function __construct(
        public Actor $actor,
        public int $targetUserId,
        public string $reason = self::RETURNED,
    ) {}
}
