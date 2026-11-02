<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\DeactivateUser;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Commands\DeactivateUser\DeactivateUserCommand;
use App\Modules\Users\Domain\Events\UserDeactivated;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** WF-05, FR-27, RULE-08. */
class DeactivateUserCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, UserModel $target): void
    {
        app(CommandBus::class)->send(new DeactivateUserCommand($actor, $target->id));
    }

    public function test_an_admin_deactivates_a_user_and_the_fact_is_announced(): void
    {
        Event::fake([UserDeactivated::class]);
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create();

        $this->send(Actor::user($admin->id), $user);

        $this->assertNotNull($user->fresh()->deactivated_at);
        Event::assertDispatched(UserDeactivated::class, fn (UserDeactivated $e): bool => $e->userId === $user->id);
    }

    public function test_an_admin_deactivates_another_admin(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $other = UserModel::factory()->admin()->create();

        $this->send(Actor::user($admin->id), $other);

        $this->assertNotNull($other->fresh()->deactivated_at);
    }

    public function test_nobody_deactivates_themself(): void
    {
        Event::fake([UserDeactivated::class]);
        $admin = UserModel::factory()->admin()->create();

        try {
            $this->send(Actor::user($admin->id), $admin);
            $this->fail('A User deactivated themself');
        } catch (UserRefused) {
            $this->assertNull($admin->fresh()->deactivated_at);
            Event::assertNotDispatched(UserDeactivated::class);
        }
    }

    public function test_nobody_deactivates_a_super_admin_not_even_a_super_admin(): void
    {
        $superAdmin = UserModel::factory()->superAdmin()->create();
        $admin = UserModel::factory()->admin()->create();
        $other = UserModel::factory()->superAdmin()->create();

        foreach ([$admin, $superAdmin] as $actor) {
            try {
                $this->send(Actor::user($actor->id), $other);
                $this->fail('A Super Admin was deactivated');
            } catch (UserRefused) {
                $this->assertNull($other->fresh()->deactivated_at);
            }
        }
    }

    public function test_a_user_without_the_permission_is_rejected(): void
    {
        $seller = UserModel::factory()->create();
        $other = UserModel::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->send(Actor::user($seller->id), $other);
    }

    public function test_an_unknown_user_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();

        $this->expectException(UserRefused::class);
        app(CommandBus::class)->send(new DeactivateUserCommand(Actor::user($admin->id), 999999));
    }

    public function test_deactivating_a_deactivated_user_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create(['deactivated_at' => now()]);

        $this->expectException(UserRefused::class);
        $this->send(Actor::user($admin->id), $user);
    }
}
