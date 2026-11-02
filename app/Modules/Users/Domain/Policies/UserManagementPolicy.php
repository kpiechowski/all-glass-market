<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Policies;

use App\Modules\Users\Domain\Enums\UserRole;

/**
 * RULE-08 on plain values. Ranks come from the Role order (D-23), so a Super
 * Admin is out of an Admin's reach without naming Admin here. The form and the
 * handler ask the same methods.
 */
final class UserManagementPolicy
{
    /** The actor may edit the target's data: nobody manages a User of a higher rank. */
    public static function canManage(int $actorRank, int $targetRank): bool
    {
        return $targetRank <= $actorRank;
    }

    /**
     * RULE-08, D-48: another Super Admin's account is read-only to everyone; a
     * Super Admin edits their own data. Otherwise the rank rule applies.
     */
    public static function canEditAccountOf(?int $actorId, int $targetId, string $targetRoleKey, int $actorRank, int $targetRank): bool
    {
        if ($actorId !== $targetId && $targetRoleKey === UserRole::SuperAdmin->value) {
            return false;
        }

        return self::canManage($actorRank, $targetRank);
    }

    /** Nobody changes their own Role, and a Super Admin's Role moves only through the console. */
    public static function canChangeRoleOf(?int $actorId, int $targetId, string $targetRoleKey): bool
    {
        return $actorId !== $targetId && $targetRoleKey !== UserRole::SuperAdmin->value;
    }

    /** A null rank means the Role is not registered. */
    public static function canAssignRole(string $roleKey, ?int $newRank, int $actorRank): bool
    {
        return $newRank !== null
            && $roleKey !== UserRole::SuperAdmin->value
            && $newRank <= $actorRank;
    }

    /** RULE-08, D-47: not oneself, never a Super Admin, nobody above the actor's rank. */
    public static function canDeactivate(?int $actorId, int $targetId, string $targetRoleKey, int $actorRank, int $targetRank): bool
    {
        return $actorId !== $targetId
            && $targetRoleKey !== UserRole::SuperAdmin->value
            && self::canManage($actorRank, $targetRank);
    }
}
