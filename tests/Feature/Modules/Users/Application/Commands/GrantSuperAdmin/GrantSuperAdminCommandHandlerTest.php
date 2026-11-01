<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\GrantSuperAdmin;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\Commands\GrantSuperAdmin\GrantSuperAdminCommand;
use App\Modules\Users\Domain\Exceptions\RoleAssignmentRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrantSuperAdminCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function console(): Actor
    {
        return Actor::system('console: test');
    }

    public function test_the_console_grants_super_admin(): void
    {
        $user = UserModel::factory()->create(['role' => 'admin']);

        app(CommandBus::class)->send(new GrantSuperAdminCommand($this->console(), $user->email));

        $this->assertSame('super_admin', $user->fresh()->role);
    }

    public function test_a_person_cannot_grant_super_admin(): void
    {
        $admin = UserModel::factory()->create(['role' => 'super_admin']);
        $user = UserModel::factory()->create(['role' => 'user']);

        try {
            app(CommandBus::class)->send(new GrantSuperAdminCommand(Actor::user($admin->id), $user->email));
            $this->fail('A person granted Super Admin');
        } catch (RoleAssignmentRefused $exception) {
            $this->assertSame('users::exceptions.super_admin_console_only', $exception->translationKey);
        }

        $this->assertSame('user', $user->fresh()->role);
    }

    public function test_granting_to_a_super_admin_or_an_unknown_user_is_refused(): void
    {
        $superAdmin = UserModel::factory()->create(['role' => 'super_admin']);

        try {
            app(CommandBus::class)->send(new GrantSuperAdminCommand($this->console(), $superAdmin->email));
            $this->fail('Granted twice');
        } catch (RoleAssignmentRefused $exception) {
            $this->assertSame('users::exceptions.already_super_admin', $exception->translationKey);
        }

        $this->expectException(RoleAssignmentRefused::class);
        app(CommandBus::class)->send(new GrantSuperAdminCommand($this->console(), 'nobody@example.com'));
    }
}
