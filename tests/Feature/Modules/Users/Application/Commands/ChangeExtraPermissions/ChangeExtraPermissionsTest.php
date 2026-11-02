<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\ChangeExtraPermissions;

use App\Core\Contracts\Authorizer;
use App\Core\Domain\Actor;
use App\Modules\Users\Application\Commands\CreateUser\CreateUserCommand;
use App\Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use App\Modules\Users\Domain\Enums\UsersPermission;
use App\Modules\Users\Domain\Events\ExtraPermissionGranted;
use App\Modules\Users\Domain\Events\ExtraPermissionRevoked;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** FR-06, RULE-11: Extra Permissions travel in the create and update commands. */
class ChangeExtraPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private const string IMPERSONATE = 'users.impersonate';

    /** @param  list<string>|null  $extra */
    private function update(UserModel $actor, UserModel $target, ?array $extra): void
    {
        app(CommandBus::class)->send(new UpdateUserCommand(
            Actor::user($actor->id), $target->id, $target->name, $target->email, extraPermissions: $extra,
        ));
    }

    public function test_a_super_admin_grants_and_revokes_and_each_change_is_announced(): void
    {
        Event::fake([ExtraPermissionGranted::class, ExtraPermissionRevoked::class]);
        $super = UserModel::factory()->superAdmin()->create();
        $admin = UserModel::factory()->admin()->create();

        $this->update($super, $admin, [self::IMPERSONATE]);

        $this->assertSame([self::IMPERSONATE], $admin->fresh()->extra_permissions);
        Event::assertDispatched(ExtraPermissionGranted::class, fn ($e): bool => $e->userId === $admin->id
            && $e->permission === self::IMPERSONATE && $e->actorId === $super->id);

        $this->update($super, $admin, []);

        $this->assertSame([], $admin->fresh()->extra_permissions ?? []);
        Event::assertDispatched(ExtraPermissionRevoked::class, fn ($e): bool => $e->permission === self::IMPERSONATE);
    }

    public function test_a_null_list_keeps_the_extra_permissions(): void
    {
        $super = UserModel::factory()->superAdmin()->create();
        $admin = UserModel::factory()->admin()->create(['extra_permissions' => [self::IMPERSONATE]]);

        $this->update($super, $admin, null);

        $this->assertSame([self::IMPERSONATE], $admin->fresh()->extra_permissions);
    }

    public function test_an_unchanged_list_announces_nothing(): void
    {
        Event::fake([ExtraPermissionGranted::class, ExtraPermissionRevoked::class]);
        $super = UserModel::factory()->superAdmin()->create();
        $admin = UserModel::factory()->admin()->create(['extra_permissions' => [self::IMPERSONATE]]);

        $this->update($super, $admin, [self::IMPERSONATE]);

        Event::assertNotDispatched(ExtraPermissionGranted::class);
        Event::assertNotDispatched(ExtraPermissionRevoked::class);
    }

    public function test_an_admin_cannot_grant_or_revoke_not_even_to_themself(): void
    {
        Event::fake([ExtraPermissionGranted::class]);
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create();
        $held = UserModel::factory()->create(['extra_permissions' => [self::IMPERSONATE]]);

        foreach ([[$user, [self::IMPERSONATE]], [$admin, [self::IMPERSONATE]], [$held, []]] as [$target, $list]) {
            try {
                $this->update($admin, $target, $list);
                $this->fail('An Admin changed Extra Permissions');
            } catch (UserRefused) {
                $this->assertTrue(true);
            }
        }

        $this->assertNull($user->fresh()->extra_permissions);
        $this->assertSame([self::IMPERSONATE], $held->fresh()->extra_permissions);
        Event::assertNotDispatched(ExtraPermissionGranted::class);
    }

    public function test_a_permission_nobody_registered_cannot_be_granted(): void
    {
        $super = UserModel::factory()->superAdmin()->create();
        $user = UserModel::factory()->create();

        $this->expectException(UserRefused::class);
        $this->update($super, $user, ['nobody.declared']);
    }

    public function test_any_registered_permission_can_be_granted(): void
    {
        $super = UserModel::factory()->superAdmin()->create();
        $user = UserModel::factory()->create();

        $this->update($super, $user, [UsersPermission::ManageUsers->value]);

        $this->assertSame([UsersPermission::ManageUsers->value], $user->fresh()->extra_permissions);
    }

    public function test_a_super_admin_creates_a_user_with_extra_permissions(): void
    {
        Event::fake([ExtraPermissionGranted::class]);
        $super = UserModel::factory()->superAdmin()->create();

        $id = app(CommandBus::class)->send(new CreateUserCommand(
            Actor::user($super->id), 'admin', 'Nowy', 'nowy@example.com', 'secret-pass-1', extraPermissions: [self::IMPERSONATE],
        ));

        $this->assertSame([self::IMPERSONATE], UserModel::findOrFail($id)->extra_permissions);
        Event::assertDispatched(ExtraPermissionGranted::class, fn ($e): bool => $e->userId === $id);
    }

    public function test_an_admin_cannot_create_a_user_with_extra_permissions(): void
    {
        $admin = UserModel::factory()->admin()->create();

        try {
            app(CommandBus::class)->send(new CreateUserCommand(
                Actor::user($admin->id), 'user', 'Nowy', 'nowy@example.com', 'secret-pass-1', extraPermissions: [self::IMPERSONATE],
            ));
            $this->fail('An Admin granted an Extra Permission');
        } catch (UserRefused) {
            $this->assertDatabaseMissing('users', ['email' => 'nowy@example.com']);
        }
    }

    public function test_an_extra_permission_makes_the_holder_pass_the_authorizer(): void
    {
        $super = UserModel::factory()->superAdmin()->create();
        $user = UserModel::factory()->create();

        $this->update($super, $user, [UsersPermission::ManageUsers->value]);

        $this->assertTrue(app(Authorizer::class)->can(Actor::user($user->id), UsersPermission::ManageUsers));
    }
}
