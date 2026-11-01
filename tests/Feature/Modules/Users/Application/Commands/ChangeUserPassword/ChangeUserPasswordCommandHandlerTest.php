<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\ChangeUserPassword;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Commands\ChangeUserPassword\ChangeUserPasswordCommand;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangeUserPasswordCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function change(Actor $actor, UserModel $target, string $password = 'brand-new-pass'): void
    {
        app(CommandBus::class)->send(new ChangeUserPasswordCommand($actor, $target->id, $password));
    }

    public function test_a_user_changes_their_own_password(): void
    {
        $user = UserModel::factory()->create();

        $this->change(Actor::user($user->id), $user);

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_an_admin_changes_a_users_password_but_not_a_super_admins(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create();
        $super = UserModel::factory()->superAdmin()->create();

        $this->change(Actor::user($admin->id), $user);
        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));

        $this->expectException(UserRefused::class);
        $this->change(Actor::user($admin->id), $super);
    }

    public function test_a_user_cannot_change_another_users_password(): void
    {
        $user = UserModel::factory()->create();
        $other = UserModel::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->change(Actor::user($user->id), $other);
    }

    public function test_a_short_password_is_refused(): void
    {
        $user = UserModel::factory()->create();

        $this->expectException(UserRefused::class);
        $this->change(Actor::user($user->id), $user, 'short');
    }

    public function test_an_admins_change_sets_password_change_required(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create();

        $this->change(Actor::user($admin->id), $user);

        $this->assertTrue($user->fresh()->password_change_required);
    }

    public function test_the_users_own_change_clears_the_marker(): void
    {
        $user = UserModel::factory()->passwordChangeRequired()->create();

        $this->change(Actor::user($user->id), $user);

        $this->assertFalse($user->fresh()->password_change_required);
    }
}
