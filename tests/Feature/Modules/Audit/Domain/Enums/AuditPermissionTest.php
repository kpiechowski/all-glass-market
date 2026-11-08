<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\Domain\Enums;

use App\Modules\Audit\Domain\Enums\AuditPermission;
use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Domain\Enums\UserRole;
use Tests\TestCase;

class AuditPermissionTest extends TestCase
{
    public function test_admin_holds_only_the_record_history_and_super_admin_holds_everything(): void
    {
        $registry = app(RoleRegistry::class);

        $this->assertContains(AuditPermission::RecordHistory, $registry->permissions());
        $this->assertContains(AuditPermission::RestoreChanges, $registry->permissions());

        foreach (AuditPermission::cases() as $permission) {
            $this->assertSame($permission === AuditPermission::RecordHistory, $registry->roleHolds(UserRole::Admin->value, $permission));
            $this->assertTrue($registry->roleHolds(UserRole::SuperAdmin->value, $permission));
            $this->assertFalse($registry->roleHolds(UserRole::User->value, $permission));
        }
    }

    public function test_every_permission_has_a_polish_label(): void
    {
        foreach (AuditPermission::cases() as $permission) {
            $key = 'audit::permissions.'.str_replace('.', '_', $permission->value);

            $this->assertNotSame($key, __($key));
        }
    }
}
