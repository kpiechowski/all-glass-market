<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\AssignUserRole;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Commands\AssignUserRole\AssignUserRoleCommand;
use App\Modules\Users\Domain\Exceptions\RoleAssignmentRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignUserRoleCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, UserModel $target, string $role): void
    {
        app(CommandBus::class)->send(new AssignUserRoleCommand($actor, $target->id, $role));
    }

    public function test_an_admin_assigns_a_registered_role(): void
    {
        $admin = UserModel::factory()->create(['role' => 'admin']);
        $user = UserModel::factory()->create(['role' => 'user']);

        $this->send(Actor::user($admin->id), $user, 'admin');

        $this->assertSame('admin', $user->fresh()->role);
    }

    public function test_a_user_without_the_permission_is_rejected(): void
    {
        $seller = UserModel::factory()->create(['role' => 'user']);
        $other = UserModel::factory()->create(['role' => 'user']);

        $this->expectException(AuthorizationException::class);
        $this->send(Actor::user($seller->id), $other, 'admin');
    }

    public function test_super_admin_cannot_be_assigned_through_the_command(): void
    {
        $superAdmin = UserModel::factory()->create(['role' => 'super_admin']);
        $user = UserModel::factory()->create(['role' => 'user']);

        $this->expectException(RoleAssignmentRefused::class);
        $this->send(Actor::user($superAdmin->id), $user, 'super_admin');
    }

    public function test_a_super_admin_cannot_be_demoted_through_the_command(): void
    {
        $superAdmin = UserModel::factory()->create(['role' => 'super_admin']);
        $target = UserModel::factory()->create(['role' => 'super_admin']);

        try {
            $this->send(Actor::user($superAdmin->id), $target, 'user');
            $this->fail('A Super Admin was demoted from the panel path');
        } catch (RoleAssignmentRefused) {
            $this->assertSame('super_admin', $target->fresh()->role);
        }
    }

    public function test_an_unregistered_role_is_refused(): void
    {
        $admin = UserModel::factory()->create(['role' => 'admin']);
        $user = UserModel::factory()->create(['role' => 'user']);

        $this->expectException(RoleAssignmentRefused::class);
        $this->send(Actor::user($admin->id), $user, 'shop_cashier');
    }
}
