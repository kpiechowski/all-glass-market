<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\RevokeSuperAdmin;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\Commands\RevokeSuperAdmin\RevokeSuperAdminCommand;
use App\Modules\Users\Domain\Exceptions\RoleAssignmentRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevokeSuperAdminCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function console(): Actor
    {
        return Actor::system('console: test');
    }

    public function test_the_console_revokes_super_admin_back_to_user(): void
    {
        $user = UserModel::factory()->create(['role' => 'super_admin']);

        app(CommandBus::class)->send(new RevokeSuperAdminCommand($this->console(), $user->email));

        $this->assertSame('user', $user->fresh()->role);
    }

    public function test_a_person_cannot_revoke_super_admin(): void
    {
        $superAdmin = UserModel::factory()->create(['role' => 'super_admin']);
        $other = UserModel::factory()->create(['role' => 'super_admin']);

        try {
            app(CommandBus::class)->send(new RevokeSuperAdminCommand(Actor::user($superAdmin->id), $other->email));
            $this->fail('A person revoked Super Admin');
        } catch (RoleAssignmentRefused) {
            $this->assertSame('super_admin', $other->fresh()->role);
        }
    }

    public function test_revoking_from_someone_who_is_not_super_admin_is_refused(): void
    {
        $user = UserModel::factory()->create(['role' => 'admin']);

        try {
            app(CommandBus::class)->send(new RevokeSuperAdminCommand($this->console(), $user->email));
            $this->fail('Revoked from a non Super Admin');
        } catch (RoleAssignmentRefused $exception) {
            $this->assertSame('users::exceptions.not_super_admin', $exception->translationKey);
        }

        $this->assertSame('admin', $user->fresh()->role);
    }
}
