<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Policies;

use App\Core\Domain\Actor;

/**
 * RULE-09: the Super Admin Role is granted and revoked only from the console,
 * which acts as the system.
 */
final class SuperAdminRolePolicy
{
    public static function canBeChangedBy(Actor $actor): bool
    {
        return $actor->isSystem();
    }
}
