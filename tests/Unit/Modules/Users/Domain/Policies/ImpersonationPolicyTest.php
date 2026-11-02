<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Users\Domain\Policies;

use App\Modules\Users\Domain\Policies\ImpersonationPolicy;
use PHPUnit\Framework\TestCase;

/** RULE-10, WF-06. */
class ImpersonationPolicyTest extends TestCase
{
    private function can(int $actor = 300, int $target = 100, string $role = 'user', bool $active = true, bool $registered = true): bool
    {
        return ImpersonationPolicy::canImpersonate($actor, $target, $role, $active, $registered);
    }

    public function test_a_lower_active_registered_target_is_allowed(): void
    {
        $this->assertTrue($this->can());
        $this->assertTrue($this->can(actor: 200, target: 100));
    }

    public function test_an_equal_or_higher_rank_is_refused(): void
    {
        $this->assertFalse($this->can(actor: 200, target: 200, role: 'admin'));
        $this->assertFalse($this->can(actor: 100, target: 200, role: 'admin'));
    }

    public function test_a_super_admin_is_never_a_target(): void
    {
        $this->assertFalse($this->can(actor: 1000, target: 300, role: 'super_admin'));
    }

    public function test_a_deactivated_target_or_an_unregistered_role_is_refused(): void
    {
        $this->assertFalse($this->can(active: false));
        $this->assertFalse($this->can(registered: false));
    }

    public function test_nesting_is_refused(): void
    {
        $this->assertTrue(ImpersonationPolicy::canStart(false));
        $this->assertFalse(ImpersonationPolicy::canStart(true));
    }
}
