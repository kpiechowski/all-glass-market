<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Policies;

use App\Modules\Users\Domain\Enums\UserRole;

/**
 * RULE-11 on plain values: only a Super Admin grants, revokes or sees Extra
 * Permissions. Decided by rank (the Role order), never by a Role's name.
 */
final class ExtraPermissionPolicy
{
    public static function canManage(int $actorRank): bool
    {
        return $actorRank >= UserRole::SuperAdmin->rank();
    }

    /**
     * Any registered Permission can be granted. Revoking needs no check, so a
     * grant whose Permission has since vanished can still be taken away.
     *
     * @param  list<string>  $grantedKeys
     * @param  list<string>  $registeredKeys
     */
    public static function allGrantsAreRegistered(array $grantedKeys, array $registeredKeys): bool
    {
        return array_diff($grantedKeys, $registeredKeys) === [];
    }
}
