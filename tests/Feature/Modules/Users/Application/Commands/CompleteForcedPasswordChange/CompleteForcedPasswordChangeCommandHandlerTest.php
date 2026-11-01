<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\CompleteForcedPasswordChange;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Commands\CompleteForcedPasswordChange\CompleteForcedPasswordChangeCommand;
use App\Modules\Users\Domain\Events\PasswordChanged;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** WF-07, FR-38. */
class CompleteForcedPasswordChangeCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function complete(Actor $actor, string $password = 'brand-new-pass'): void
    {
        app(CommandBus::class)->send(new CompleteForcedPasswordChangeCommand($actor, $password));
    }

    public function test_it_sets_the_password_clears_the_marker_and_announces_a_forced_change(): void
    {
        Event::fake([PasswordChanged::class]);
        $user = UserModel::factory()->passwordChangeRequired()->create(['password' => 'temporary-pass']);

        $this->complete(Actor::user($user->id));

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('brand-new-pass', $fresh->password));
        $this->assertFalse($fresh->password_change_required);
        Event::assertDispatched(PasswordChanged::class, fn (PasswordChanged $e): bool => $e->userId === $user->id && $e->reason === 'forced');
    }

    public function test_the_same_password_is_refused_and_nothing_changes(): void
    {
        Event::fake([PasswordChanged::class]);
        $user = UserModel::factory()->passwordChangeRequired()->create(['password' => 'temporary-pass']);

        try {
            $this->complete(Actor::user($user->id), 'temporary-pass');
            $this->fail('Expected a refusal.');
        } catch (UserRefused $e) {
            $this->assertSame('users::exceptions.password_same_as_current', $e->translationKey);
        }

        $this->assertTrue($user->fresh()->password_change_required);
        Event::assertNotDispatched(PasswordChanged::class);
    }

    public function test_a_short_password_is_refused(): void
    {
        $user = UserModel::factory()->passwordChangeRequired()->create();

        $this->expectException(UserRefused::class);
        $this->complete(Actor::user($user->id), 'short');
    }

    public function test_it_is_refused_when_no_change_is_required(): void
    {
        $user = UserModel::factory()->create();

        $this->expectException(UserRefused::class);
        $this->complete(Actor::user($user->id));
    }

    public function test_the_system_cannot_use_it(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->complete(Actor::system('test'));
    }
}
