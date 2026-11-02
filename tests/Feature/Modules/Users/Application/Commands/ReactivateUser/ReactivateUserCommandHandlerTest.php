<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\ReactivateUser;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Commands\ReactivateUser\ReactivateUserCommand;
use App\Modules\Users\Domain\Events\UserReactivated;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** WF-05, FR-27, RULE-08. */
class ReactivateUserCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, UserModel $target): void
    {
        app(CommandBus::class)->send(new ReactivateUserCommand($actor, $target->id));
    }

    public function test_an_admin_reactivates_a_user_and_the_fact_is_announced(): void
    {
        Event::fake([UserReactivated::class]);
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->deactivated()->create();

        $this->send(Actor::user($admin->id), $user);

        $this->assertNull($user->fresh()->deactivated_at);
        Event::assertDispatched(UserReactivated::class, fn (UserReactivated $e): bool => $e->userId === $user->id);
    }

    public function test_an_admin_cannot_reactivate_a_user_above_their_rank(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $super = UserModel::factory()->superAdmin()->deactivated()->create();

        $this->expectException(UserRefused::class);
        $this->send(Actor::user($admin->id), $super);
    }

    public function test_reactivating_an_active_user_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create();

        $this->expectException(UserRefused::class);
        $this->send(Actor::user($admin->id), $user);
    }

    public function test_a_user_without_the_permission_is_rejected(): void
    {
        $seller = UserModel::factory()->create();
        $user = UserModel::factory()->deactivated()->create();

        $this->expectException(AuthorizationException::class);
        $this->send(Actor::user($seller->id), $user);
    }
}
