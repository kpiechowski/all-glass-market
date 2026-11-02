<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\SetPasswordChangeRequirement;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Commands\SetPasswordChangeRequirement\SetPasswordChangeRequirementCommand;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** FR-26, RULE-15. */
class SetPasswordChangeRequirementCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function set(Actor $actor, UserModel $target, bool $required): void
    {
        app(CommandBus::class)->send(new SetPasswordChangeRequirementCommand($actor, $target->id, $required));
    }

    public function test_an_admin_sets_and_clears_the_marker_without_touching_the_password(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create(['password' => 'keep-this-pass']);

        $this->set(Actor::user($admin->id), $user, true);
        $this->assertTrue($user->fresh()->password_change_required);

        $this->set(Actor::user($admin->id), $user, false);
        $this->assertFalse($user->fresh()->password_change_required);
        $this->assertTrue(Hash::check('keep-this-pass', $user->fresh()->password));
    }

    public function test_it_applies_to_a_super_admin_only_when_the_actor_may_manage_them(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $super = UserModel::factory()->superAdmin()->create();

        $this->expectException(UserRefused::class);
        $this->set(Actor::user($admin->id), $super, true);
    }

    public function test_a_super_admin_cannot_mark_another_super_admin_but_marks_admins(): void
    {
        $super = UserModel::factory()->superAdmin()->create();
        $other = UserModel::factory()->superAdmin()->create();
        $admin = UserModel::factory()->admin()->create();

        $this->set(Actor::user($super->id), $admin, true);
        $this->assertTrue($admin->fresh()->password_change_required);

        $this->expectException(UserRefused::class);
        $this->set(Actor::user($super->id), $other, true);
    }

    public function test_a_user_without_the_permission_is_rejected(): void
    {
        $seller = UserModel::factory()->create();
        $other = UserModel::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->set(Actor::user($seller->id), $other, true);
    }
}
