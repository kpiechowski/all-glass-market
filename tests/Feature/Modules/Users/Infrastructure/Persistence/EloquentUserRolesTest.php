<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Persistence;

use App\Modules\Users\Domain\Models\User;
use App\Modules\Users\Domain\Repositories\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentUserRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reads_and_assigns_a_role(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $roles = app(UserRoles::class);

        $this->assertSame('user', $roles->roleKeyOf($user->id));
        $this->assertNull($roles->roleKeyOf(9999));

        $roles->assign($user->id, 'admin');

        $this->assertSame('admin', $roles->roleKeyOf($user->id));
    }

    public function test_it_finds_a_user_by_email(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com']);

        $this->assertSame($user->id, app(UserRoles::class)->userIdByEmail('a@example.com'));
        $this->assertNull(app(UserRoles::class)->userIdByEmail('missing@example.com'));
    }

    public function test_it_lists_ids_with_the_given_roles_and_excludes_others(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'user']);

        $this->assertSame([$admin->id], app(UserRoles::class)->userIdsWithRoles(['admin', 'super_admin']));
    }
}
