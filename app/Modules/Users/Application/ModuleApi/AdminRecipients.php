<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\ModuleApi;

use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Repositories\UserRepository;

/**
 * The "Admins" recipient group (D-12): the one place a Role name decides
 * something. Other modules use it to notify AllGlass staff.
 */
final class AdminRecipients
{
    public function __construct(private UserRepository $users) {}

    /**
     * Ids of active Users whose Role is Admin or Super Admin (D-47).
     *
     * @return list<int>
     */
    public function userIds(): array
    {
        return $this->users->userIdsWithRoles([UserRole::Admin->value, UserRole::SuperAdmin->value]);
    }
}
