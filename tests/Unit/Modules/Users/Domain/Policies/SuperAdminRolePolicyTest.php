<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Users\Domain\Policies;

use App\Core\Domain\Actor;
use App\Modules\Users\Domain\Policies\SuperAdminRolePolicy;
use PHPUnit\Framework\TestCase;

class SuperAdminRolePolicyTest extends TestCase
{
    public function test_the_console_actor_may_change_the_super_admin_role(): void
    {
        $this->assertTrue(SuperAdminRolePolicy::canBeChangedBy(Actor::system('console')));
    }

    public function test_a_person_may_not(): void
    {
        $this->assertFalse(SuperAdminRolePolicy::canBeChangedBy(Actor::user(1)));
    }
}
