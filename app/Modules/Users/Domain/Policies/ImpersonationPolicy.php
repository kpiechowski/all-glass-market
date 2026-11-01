<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Policies;

use App\Modules\Users\Domain\Enums\UserRole;

/**
 * RULE-10, WF-06 on plain values: the target ranks lower than the impersonator,
 * is never a Super Admin, is active and holds a registered Role. The form and
 * the handler ask the same methods.
 */
final class ImpersonationPolicy
{
    public static function canImpersonate(
        int $actorRank,
        int $targetRank,
        string $targetRoleKey,
        bool $targetIsActive,
        bool $targetRoleIsRegistered,
    ): bool {
        return $targetRank < $actorRank
            && $targetRoleKey !== UserRole::SuperAdmin->value
            && $targetIsActive
            && $targetRoleIsRegistered;
    }

    /** RULE-10: impersonations are never nested. */
    public static function canStart(bool $alreadyImpersonating): bool
    {
        return ! $alreadyImpersonating;
    }
}
