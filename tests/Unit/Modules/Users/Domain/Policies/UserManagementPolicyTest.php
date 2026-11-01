<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Users\Domain\Policies;

use App\Modules\Users\Domain\Policies\UserManagementPolicy;
use PHPUnit\Framework\TestCase;

class UserManagementPolicyTest extends TestCase
{
    public function test_an_actor_manages_users_of_equal_or_lower_rank_only(): void
    {
        $this->assertTrue(UserManagementPolicy::canManage(actorRank: 200, targetRank: 200));
        $this->assertTrue(UserManagementPolicy::canManage(actorRank: 200, targetRank: 100));
        $this->assertFalse(UserManagementPolicy::canManage(actorRank: 200, targetRank: 300));
    }

    public function test_nobody_changes_their_own_role(): void
    {
        $this->assertFalse(UserManagementPolicy::canChangeRoleOf(actorId: 5, targetId: 5, targetRoleKey: 'admin'));
        $this->assertTrue(UserManagementPolicy::canChangeRoleOf(actorId: 5, targetId: 6, targetRoleKey: 'admin'));
        $this->assertTrue(UserManagementPolicy::canChangeRoleOf(actorId: null, targetId: 6, targetRoleKey: 'admin'));
    }

    public function test_a_super_admin_role_never_changes_from_the_panel(): void
    {
        $this->assertFalse(UserManagementPolicy::canChangeRoleOf(actorId: 1, targetId: 2, targetRoleKey: 'super_admin'));
    }

    public function test_a_role_is_assignable_when_registered_not_super_admin_and_not_above_the_actor(): void
    {
        $this->assertTrue(UserManagementPolicy::canAssignRole('admin', newRank: 200, actorRank: 200));
        $this->assertTrue(UserManagementPolicy::canAssignRole('user', newRank: 100, actorRank: 200));
        $this->assertFalse(UserManagementPolicy::canAssignRole('super_admin', newRank: 300, actorRank: 300));
        $this->assertFalse(UserManagementPolicy::canAssignRole('admin', newRank: 200, actorRank: 100));
        $this->assertFalse(UserManagementPolicy::canAssignRole('ghost', newRank: null, actorRank: 300));
    }
}
