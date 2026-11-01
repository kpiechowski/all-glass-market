<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Repositories;

use App\Core\Contracts\Repository;
use App\Modules\Users\Domain\Entities\User;

/**
 * @extends Repository<User>
 */
interface UserRepository extends Repository
{
    public function exists(int $id): bool;

    public function findByEmail(string $email): ?User;

    /** RULE-01: unique among all Users; the User being edited is excluded. */
    public function emailIsTaken(string $email, ?int $exceptUserId = null): bool;

    /** The Role key, or null when no such User exists. */
    public function roleKeyOf(int $userId): ?string;

    /**
     * Active Users only: a Deactivated User receives no group notification.
     *
     * @param  list<string>  $roleKeys
     * @return list<int>
     */
    public function userIdsWithRoles(array $roleKeys): array;
}
