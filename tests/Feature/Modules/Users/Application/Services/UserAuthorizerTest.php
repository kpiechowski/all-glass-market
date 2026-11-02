<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Services;

use App\Core\Contracts\Authorizer;
use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\ModuleApi\RoleRegistry;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\Users\ShopPermission;
use Tests\Fixtures\Users\ShopRole;
use Tests\TestCase;

class UserAuthorizerTest extends TestCase
{
    use RefreshDatabase;

    private function actorWithRole(string $role): Actor
    {
        return Actor::user(UserModel::factory()->create(['role' => $role])->id);
    }

    private function declareShop(): void
    {
        $registry = app(RoleRegistry::class);
        $registry->declareRole(ShopRole::Cashier);
        $registry->grant(ShopRole::Cashier, ShopPermission::Sell);
    }

    public function test_it_is_the_authorizer_the_core_contract_resolves_to(): void
    {
        $this->assertTrue(app(Authorizer::class)->can(Actor::system('test'), ShopPermission::Sell));
    }

    public function test_super_admin_passes_a_permission_no_module_declared_yet(): void
    {
        $this->assertTrue(app(Authorizer::class)->can($this->actorWithRole('super_admin'), ShopPermission::Refund));
    }

    public function test_a_granted_role_passes(): void
    {
        $this->declareShop();

        $this->assertTrue(app(Authorizer::class)->can($this->actorWithRole('shop_cashier'), ShopPermission::Sell));
    }

    public function test_an_ungranted_role_fails(): void
    {
        $this->declareShop();

        $this->assertFalse(app(Authorizer::class)->can($this->actorWithRole('shop_cashier'), ShopPermission::Refund));
        $this->assertFalse(app(Authorizer::class)->can($this->actorWithRole('admin'), ShopPermission::Sell));
    }

    public function test_a_role_whose_module_is_absent_is_not_registered_and_holds_nothing(): void
    {
        $this->assertFalse(app(RoleRegistry::class)->isRegistered('shop_cashier'));
        $this->assertFalse(app(Authorizer::class)->can($this->actorWithRole('shop_cashier'), ShopPermission::Sell));
    }

    public function test_an_actor_that_is_no_known_user_fails(): void
    {
        $this->assertFalse(app(Authorizer::class)->can(Actor::user(9999), ShopPermission::Sell));
    }

    public function test_ensure_throws_on_a_refusal_and_stays_silent_on_a_pass(): void
    {
        $this->declareShop();
        $authorizer = app(Authorizer::class);

        $authorizer->ensure($this->actorWithRole('shop_cashier'), ShopPermission::Sell);

        $this->expectException(AuthorizationException::class);
        $authorizer->ensure($this->actorWithRole('shop_cashier'), ShopPermission::Refund);
    }

    /** FR-06: the second source. */
    public function test_an_extra_permission_passes_beyond_the_role(): void
    {
        $this->declareShop();
        $user = UserModel::factory()->create(['role' => 'shop_cashier', 'extra_permissions' => [ShopPermission::Refund->value]]);
        $authorizer = app(Authorizer::class);

        $this->assertTrue($authorizer->can(Actor::user($user->id), ShopPermission::Refund));
        $this->assertTrue($authorizer->can(Actor::user($user->id), ShopPermission::Sell));
    }

    public function test_an_extra_permission_does_not_leak_to_other_permissions_or_users(): void
    {
        $this->declareShop();
        $holder = UserModel::factory()->admin()->create(['extra_permissions' => [ShopPermission::Sell->value]]);
        $other = UserModel::factory()->admin()->create();
        $authorizer = app(Authorizer::class);

        $this->assertFalse($authorizer->can(Actor::user($holder->id), ShopPermission::Refund));
        $this->assertFalse($authorizer->can(Actor::user($other->id), ShopPermission::Sell));
    }
}
