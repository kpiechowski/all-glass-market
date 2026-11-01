<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Console;

use App\Modules\Users\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminConsoleCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_grant_command_makes_the_user_super_admin(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->artisan('users:grant-super-admin', ['email' => $user->email])->assertSuccessful();

        $this->assertSame('super_admin', $user->fresh()->role);
    }

    public function test_the_revoke_command_demotes_the_user(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->artisan('users:revoke-super-admin', ['email' => $user->email])->assertSuccessful();

        $this->assertSame('user', $user->fresh()->role);
    }

    public function test_a_refusal_is_reported_and_fails(): void
    {
        $this->artisan('users:grant-super-admin', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
