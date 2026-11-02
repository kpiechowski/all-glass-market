<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\UpdateUser;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use App\Modules\Users\Domain\Exceptions\RoleAssignmentRefused;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateUserCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function update(Actor $actor, UserModel $target, ?string $role = null, ?string $password = null, ?string $email = null, ?string $shipping = '<p>new</p>'): void
    {
        app(CommandBus::class)->send(new UpdateUserCommand(
            $actor, $target->id, 'Zmieniony', $email ?? $target->email, phone: '999', companyName: 'Nowa',
            shippingText: $shipping, role: $role, password: $password,
        ));
    }

    public function test_an_admin_edits_a_users_data_and_shipping_text(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $target = UserModel::factory()->create();

        $this->update(Actor::user($admin->id), $target);

        $fresh = $target->fresh();
        $this->assertSame('Zmieniony', $fresh->name);
        $this->assertSame('Nowa', $fresh->company_name);
        $this->assertSame('<p>new</p>', $fresh->shipping_note);
        $this->assertSame('user', $fresh->role);
    }

    public function test_an_empty_password_keeps_the_current_one_and_a_new_one_replaces_it(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $target = UserModel::factory()->create();

        $this->update(Actor::user($admin->id), $target, password: '');
        $this->assertTrue(Hash::check('password', $target->fresh()->password));

        $this->update(Actor::user($admin->id), $target, password: 'brand-new-pass');
        $this->assertTrue(Hash::check('brand-new-pass', $target->fresh()->password));
    }

    public function test_a_short_password_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $target = UserModel::factory()->create();

        $this->expectException(UserRefused::class);
        $this->update(Actor::user($admin->id), $target, password: 'short');
    }

    public function test_an_email_taken_by_another_user_is_refused_but_ones_own_is_fine(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $target = UserModel::factory()->create();
        UserModel::factory()->create(['email' => 'taken@example.com']);

        $this->update(Actor::user($admin->id), $target);

        $this->expectException(UserRefused::class);
        $this->update(Actor::user($admin->id), $target, email: 'taken@example.com');
    }

    public function test_an_admin_cannot_edit_a_super_admin(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $super = UserModel::factory()->superAdmin()->create();

        try {
            $this->update(Actor::user($admin->id), $super);
            $this->fail('An Admin edited a Super Admin');
        } catch (UserRefused) {
            $this->assertNotSame('Zmieniony', $super->fresh()->name);
        }
    }

    public function test_a_super_admin_cannot_edit_another_super_admin_but_edits_users_admins_and_self(): void
    {
        $super = UserModel::factory()->superAdmin()->create();
        $other = UserModel::factory()->superAdmin()->create(['name' => 'Original']);

        try {
            $this->update(Actor::user($super->id), $other);
            $this->fail('A Super Admin edited another Super Admin');
        } catch (UserRefused) {
            $this->assertSame('Original', $other->fresh()->name);
        }

        foreach ([UserModel::factory()->create(), UserModel::factory()->admin()->create(), $super] as $target) {
            $this->update(Actor::user($super->id), $target);
            $this->assertSame('Zmieniony', $target->fresh()->name);
        }
    }

    public function test_an_admin_demotes_an_admin_to_user(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $other = UserModel::factory()->admin()->create();

        $this->update(Actor::user($admin->id), $other, role: 'user');

        $this->assertSame('user', $other->fresh()->role);
    }

    public function test_nobody_changes_their_own_role(): void
    {
        $admin = UserModel::factory()->admin()->create();

        try {
            $this->update(Actor::user($admin->id), $admin, role: 'user');
            $this->fail('An Admin changed their own Role');
        } catch (UserRefused) {
            $this->assertSame('admin', $admin->fresh()->role);
        }
    }

    public function test_an_admin_may_save_themself_with_an_unchanged_role(): void
    {
        $admin = UserModel::factory()->admin()->create();

        $this->update(Actor::user($admin->id), $admin, role: 'admin');

        $this->assertSame('Zmieniony', $admin->fresh()->name);
    }

    public function test_super_admin_cannot_be_assigned_or_removed_through_the_edit(): void
    {
        $super = UserModel::factory()->superAdmin()->create();
        $user = UserModel::factory()->create();
        $other = UserModel::factory()->superAdmin()->create();

        foreach ([[$user, 'super_admin'], [$other, 'user']] as [$target, $role]) {
            try {
                $this->update(Actor::user($super->id), $target, role: $role);
                $this->fail('Super Admin moved from the panel');
            } catch (RoleAssignmentRefused|UserRefused) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_an_unregistered_role_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $target = UserModel::factory()->create();

        $this->expectException(RoleAssignmentRefused::class);
        $this->update(Actor::user($admin->id), $target, role: 'ghost');
    }

    public function test_an_admin_can_repair_a_user_whose_role_is_unregistered(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $target = UserModel::factory()->withRole('ghost')->create();

        $this->update(Actor::user($admin->id), $target, role: 'user');

        $this->assertSame('user', $target->fresh()->role);
    }

    public function test_a_user_edits_only_themself_and_cannot_change_role_or_shipping_text(): void
    {
        $seller = UserModel::factory()->create(['shipping_note' => 'original']);
        $other = UserModel::factory()->create();

        $this->update(Actor::user($seller->id), $seller);
        $this->assertSame('Zmieniony', $seller->fresh()->name);
        $this->assertSame('original', $seller->fresh()->shipping_note);

        try {
            $this->update(Actor::user($seller->id), $seller, role: 'admin');
            $this->fail('A User changed their Role');
        } catch (AuthorizationException) {
            $this->assertSame('user', $seller->fresh()->role);
        }

        $this->expectException(AuthorizationException::class);
        $this->update(Actor::user($seller->id), $other);
    }

    public function test_an_unknown_user_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $ghost = (new UserModel)->forceFill(['id' => 9999, 'email' => 'x@example.com']);

        $this->expectException(UserRefused::class);
        $this->update(Actor::user($admin->id), $ghost);
    }

    public function test_an_admins_new_password_sets_the_marker_and_an_empty_one_leaves_it(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $target = UserModel::factory()->create();

        $this->update(Actor::user($admin->id), $target, password: '');
        $this->assertFalse($target->fresh()->password_change_required);

        $this->update(Actor::user($admin->id), $target, password: 'brand-new-pass');
        $this->assertTrue($target->fresh()->password_change_required);
    }

    public function test_a_users_own_new_password_clears_the_marker(): void
    {
        $user = UserModel::factory()->passwordChangeRequired()->create();

        $this->update(Actor::user($user->id), $user, password: 'brand-new-pass');

        $this->assertFalse($user->fresh()->password_change_required);
    }
}
