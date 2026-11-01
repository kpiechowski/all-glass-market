<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\StartImpersonation;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Commands\StartImpersonation\StartImpersonationCommand;
use App\Modules\Users\Domain\Events\ImpersonationStarted;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** WF-06, FR-28, RULE-10. */
class StartImpersonationCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function start(UserModel $actor, UserModel $target, bool $nested = false): void
    {
        app(CommandBus::class)->send(new StartImpersonationCommand(Actor::user($actor->id), $target->id, $nested));
    }

    private function adminWithImpersonation(): UserModel
    {
        return UserModel::factory()->admin()->create(['extra_permissions' => ['users.impersonate']]);
    }

    public function test_a_super_admin_impersonates_an_admin_and_the_start_is_announced(): void
    {
        Event::fake([ImpersonationStarted::class]);
        $super = UserModel::factory()->superAdmin()->create();
        $admin = UserModel::factory()->admin()->create();

        $this->start($super, $admin);

        Event::assertDispatched(ImpersonationStarted::class, fn ($e): bool => $e->impersonatorId === $super->id && $e->targetUserId === $admin->id);
    }

    public function test_an_admin_with_the_extra_permission_impersonates_a_user(): void
    {
        Event::fake([ImpersonationStarted::class]);

        $this->start($this->adminWithImpersonation(), UserModel::factory()->create());

        Event::assertDispatched(ImpersonationStarted::class);
    }

    public function test_an_admin_without_the_extra_permission_is_rejected(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->start(UserModel::factory()->admin()->create(), UserModel::factory()->create());
    }

    public function test_nobody_impersonates_a_super_admin_not_even_a_super_admin(): void
    {
        Event::fake([ImpersonationStarted::class]);
        $target = UserModel::factory()->superAdmin()->create();

        foreach ([$this->adminWithImpersonation(), UserModel::factory()->superAdmin()->create()] as $actor) {
            try {
                $this->start($actor, $target);
                $this->fail('A Super Admin was impersonated');
            } catch (UserRefused) {
                $this->assertTrue(true);
            }
        }

        Event::assertNotDispatched(ImpersonationStarted::class);
    }

    public function test_an_equal_rank_is_refused(): void
    {
        $this->expectException(UserRefused::class);
        $this->start($this->adminWithImpersonation(), UserModel::factory()->admin()->create());
    }

    public function test_a_higher_rank_holder_of_the_extra_permission_cannot_reach_up(): void
    {
        $user = UserModel::factory()->create(['extra_permissions' => ['users.impersonate']]);

        $this->expectException(UserRefused::class);
        $this->start($user, UserModel::factory()->admin()->create());
    }

    public function test_a_deactivated_target_is_refused(): void
    {
        $this->expectException(UserRefused::class);
        $this->start($this->adminWithImpersonation(), UserModel::factory()->deactivated()->create());
    }

    public function test_a_target_with_an_unregistered_role_is_refused(): void
    {
        $this->expectException(UserRefused::class);
        $this->start($this->adminWithImpersonation(), UserModel::factory()->withRole('gone_role')->create());
    }

    public function test_nesting_is_refused(): void
    {
        Event::fake([ImpersonationStarted::class]);

        try {
            $this->start(UserModel::factory()->superAdmin()->create(), UserModel::factory()->create(), nested: true);
            $this->fail('Impersonation was nested');
        } catch (UserRefused) {
            Event::assertNotDispatched(ImpersonationStarted::class);
        }
    }

    public function test_an_unknown_target_is_refused(): void
    {
        $super = UserModel::factory()->superAdmin()->create();

        $this->expectException(UserRefused::class);
        app(CommandBus::class)->send(new StartImpersonationCommand(Actor::user($super->id), 999999));
    }
}
