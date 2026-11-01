<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\ModuleApi;

use App\Modules\Users\Domain\Enums\UserRole;
use App\Modules\Users\Domain\Repositories\UserRoles;

/**
 * The "Admins" recipient group (D-12): the one place a Role name decides
 * something. Other modules use it to notify AllGlass staff.
 */
final class AdminRecipients
{
    public function __construct(private UserRoles $userRoles) {}

    /**
     * Ids of Users whose Role is Admin or Super Admin.
     *
     * Every User counts as active until ticket 07 adds deactivation; that
     * ticket must leave Deactivated Users out here.
     *
     * @return list<int>
     */
    public function userIds(): array
    {
        return $this->userRoles->userIdsWithRoles([UserRole::Admin->value, UserRole::SuperAdmin->value]);
    }
}
