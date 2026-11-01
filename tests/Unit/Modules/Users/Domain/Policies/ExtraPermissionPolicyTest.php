<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Users\Domain\Policies;

use App\Modules\Users\Domain\Policies\ExtraPermissionPolicy;
use PHPUnit\Framework\TestCase;

/** RULE-11. */
class ExtraPermissionPolicyTest extends TestCase
{
    public function test_only_the_super_admin_rank_manages_extra_permissions(): void
    {
        $this->assertTrue(ExtraPermissionPolicy::canManage(300));
        $this->assertFalse(ExtraPermissionPolicy::canManage(200));
        $this->assertFalse(ExtraPermissionPolicy::canManage(100));
    }

    public function test_every_grant_must_be_registered(): void
    {
        $this->assertTrue(ExtraPermissionPolicy::allGrantsAreRegistered(['a'], ['a', 'b']));
        $this->assertTrue(ExtraPermissionPolicy::allGrantsAreRegistered([], []));
        $this->assertFalse(ExtraPermissionPolicy::allGrantsAreRegistered(['a', 'x'], ['a', 'b']));
    }
}
