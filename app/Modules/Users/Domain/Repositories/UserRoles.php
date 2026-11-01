<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Repositories;

/**
 * Port on the Role a User holds. A User holds exactly one (FR-04), so the
 * Role is a single key on the User.
 */
interface UserRoles
{
    /**
     * The Role key, or null when no such User exists.
     */
    public function roleKeyOf(int $userId): ?string;

    public function assign(int $userId, string $roleKey): void;

    public function userIdByEmail(string $email): ?int;

    /**
     * @param  list<string>  $roleKeys
     * @return list<int>
     */
    public function userIdsWithRoles(array $roleKeys): array;
}
