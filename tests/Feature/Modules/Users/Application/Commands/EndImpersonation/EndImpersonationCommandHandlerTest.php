<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\EndImpersonation;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\Commands\EndImpersonation\EndImpersonationCommand;
use App\Modules\Users\Domain\Events\ImpersonationEnded;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** WF-06: the end is announced even when the impersonator no longer holds the Permission. */
class EndImpersonationCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_end_is_announced_with_its_reason(): void
    {
        Event::fake([ImpersonationEnded::class]);
        $admin = UserModel::factory()->admin()->create();
        $target = UserModel::factory()->create();

        app(CommandBus::class)->send(new EndImpersonationCommand(Actor::user($admin->id), $target->id));
        app(CommandBus::class)->send(new EndImpersonationCommand(Actor::user($admin->id), $target->id, EndImpersonationCommand::TARGET_DEACTIVATED));

        Event::assertDispatched(ImpersonationEnded::class, fn ($e): bool => $e->reason === 'returned' && $e->impersonatorId === $admin->id);
        Event::assertDispatched(ImpersonationEnded::class, fn ($e): bool => $e->reason === 'target_deactivated');
    }
}
