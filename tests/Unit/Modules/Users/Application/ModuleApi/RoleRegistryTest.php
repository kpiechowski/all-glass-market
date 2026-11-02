<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Users\Application\ModuleApi;

use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Domain\Enums\UserRole;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Users\ShopPermission;
use Tests\Fixtures\Users\ShopRole;

class RoleRegistryTest extends TestCase
{
    public function test_a_declared_role_is_registered_and_an_undeclared_one_is_not(): void
    {
        $registry = new RoleRegistry;
        $registry->declareRole(UserRole::Admin, 200);

        $this->assertTrue($registry->isRegistered('admin'));
        $this->assertFalse($registry->isRegistered(ShopRole::Cashier->value));
        $this->assertFalse($registry->isRegistered('nonsense'));
    }

    public function test_a_role_ranks_at_the_user_level_unless_the_module_states_otherwise(): void
    {
        $registry = new RoleRegistry;
        $registry->declareRole(UserRole::User, RoleRegistry::DEFAULT_RANK);
        $registry->declareRole(ShopRole::Cashier);

        $this->assertSame($registry->rankOf('user'), $registry->rankOf('shop_cashier'));
        $this->assertNull($registry->rankOf('nonsense'));
    }

    public function test_a_grant_is_held_only_by_the_role_it_was_made_to(): void
    {
        $registry = new RoleRegistry;
        $registry->declareRole(ShopRole::Cashier);
        $registry->declareRole(UserRole::User);
        $registry->grant(ShopRole::Cashier, ShopPermission::Sell);

        $this->assertTrue($registry->roleHolds('shop_cashier', ShopPermission::Sell));
        $this->assertFalse($registry->roleHolds('shop_cashier', ShopPermission::Refund));
        $this->assertFalse($registry->roleHolds('user', ShopPermission::Sell));
    }

    public function test_a_module_can_grant_its_permission_to_a_role_another_module_declared(): void
    {
        $registry = new RoleRegistry;
        $registry->declareRole(UserRole::User);
        $registry->grant(UserRole::User, ShopPermission::Sell);

        $this->assertTrue($registry->roleHolds('user', ShopPermission::Sell));
    }

    public function test_a_role_declared_to_hold_every_permission_holds_one_nobody_declared(): void
    {
        $registry = new RoleRegistry;
        $registry->declareRole(UserRole::SuperAdmin, 300, holdsEveryPermission: true);
        $registry->declareRole(UserRole::Admin, 200);

        $this->assertTrue($registry->roleHolds('super_admin', ShopPermission::Refund));
        $this->assertFalse($registry->roleHolds('admin', ShopPermission::Refund));
    }

    public function test_an_unregistered_role_holds_nothing_even_when_a_grant_was_made_to_it(): void
    {
        $registry = new RoleRegistry;
        $registry->grant(ShopRole::Cashier, ShopPermission::Sell);

        $this->assertFalse($registry->roleHolds('shop_cashier', ShopPermission::Sell));
    }

    public function test_it_lists_declared_permissions_by_enum(): void
    {
        $registry = new RoleRegistry;
        $registry->declarePermissions(ShopPermission::class);

        $this->assertSame([ShopPermission::Sell, ShopPermission::Refund], $registry->permissions());
    }
}
