<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\SetPasswordFromLink;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Commands\SendPasswordLink\SendPasswordLinkCommand;
use App\Modules\Users\Application\Commands\SetPasswordFromLink\SetPasswordFromLinkCommand;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Domain\Events\PasswordChanged;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CapturesPasswordLinks;
use Tests\TestCase;

/** WF-03, WF-04 (invitation), RULE-14, RULE-15. */
class SetPasswordFromLinkCommandHandlerTest extends TestCase
{
    use CapturesPasswordLinks, RefreshDatabase;

    /** @return array{url: string, email: string, token: string} */
    private function issue(UserModel $user, PasswordLinkKind $kind): array
    {
        $this->emailSendingWorks();
        $admin = UserModel::factory()->admin()->create();
        app(CommandBus::class)->send(new SendPasswordLinkCommand(Actor::user($admin->id), $user->id, $kind->value));

        return $this->sentLink($user, $kind);
    }

    private function redeem(array $link, PasswordLinkKind $kind, string $password = 'brand-new-pass'): int
    {
        return app(CommandBus::class)->send(new SetPasswordFromLinkCommand(
            Actor::system('test'), $link['email'], $link['token'], $kind->value, $password,
        ));
    }

    public function test_a_reset_link_sets_the_password_and_clears_the_marker(): void
    {
        Event::fake([PasswordChanged::class]);
        $user = UserModel::factory()->passwordChangeRequired()->create();
        $link = $this->issue($user, PasswordLinkKind::Reset);

        $id = $this->redeem($link, PasswordLinkKind::Reset);

        $this->assertSame($user->id, $id);
        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
        $this->assertFalse($user->fresh()->password_change_required);
        Event::assertDispatched(PasswordChanged::class, fn (PasswordChanged $e): bool => $e->reason === 'reset');
    }

    public function test_an_invitation_link_sets_the_password(): void
    {
        $user = UserModel::factory()->create();
        $link = $this->issue($user, PasswordLinkKind::Invitation);

        $this->redeem($link, PasswordLinkKind::Invitation);

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_a_link_works_once(): void
    {
        $user = UserModel::factory()->create();
        $link = $this->issue($user, PasswordLinkKind::Reset);
        $this->redeem($link, PasswordLinkKind::Reset);

        $this->expectException(UserRefused::class);
        $this->redeem($link, PasswordLinkKind::Reset, 'another-new-pass');
    }

    public function test_a_reset_link_expires_after_60_minutes(): void
    {
        $user = UserModel::factory()->create(['password' => 'old-password']);
        $link = $this->issue($user, PasswordLinkKind::Reset);

        $this->travel(61)->minutes();

        try {
            $this->redeem($link, PasswordLinkKind::Reset);
            $this->fail('Expected a refusal.');
        } catch (UserRefused $e) {
            $this->assertSame('users::exceptions.password_link_invalid', $e->translationKey);
        }

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_a_reset_link_is_still_valid_after_59_minutes(): void
    {
        $user = UserModel::factory()->create();
        $link = $this->issue($user, PasswordLinkKind::Reset);

        $this->travel(59)->minutes();

        $this->assertSame($user->id, $this->redeem($link, PasswordLinkKind::Reset));
    }

    public function test_an_invitation_link_lasts_seven_days_but_not_longer(): void
    {
        $user = UserModel::factory()->create();
        $link = $this->issue($user, PasswordLinkKind::Invitation);

        $this->travel(6)->days();
        $this->assertSame($user->id, $this->redeem($link, PasswordLinkKind::Invitation));

        $other = UserModel::factory()->create();
        $expired = $this->issue($other, PasswordLinkKind::Invitation);
        $this->travel(8)->days();

        $this->expectException(UserRefused::class);
        $this->redeem($expired, PasswordLinkKind::Invitation);
    }

    public function test_a_token_cannot_be_used_for_the_other_kind(): void
    {
        $user = UserModel::factory()->create();
        $link = $this->issue($user, PasswordLinkKind::Invitation);

        $this->expectException(UserRefused::class);
        $this->redeem($link, PasswordLinkKind::Reset);
    }

    public function test_a_new_link_voids_the_earlier_one(): void
    {
        $user = UserModel::factory()->create();
        $first = $this->issue($user, PasswordLinkKind::Reset);
        $this->issue($user, PasswordLinkKind::Reset);

        $this->expectException(UserRefused::class);
        $this->redeem($first, PasswordLinkKind::Reset);
    }

    public function test_a_short_password_is_refused_and_the_link_survives(): void
    {
        $user = UserModel::factory()->create();
        $link = $this->issue($user, PasswordLinkKind::Reset);

        try {
            $this->redeem($link, PasswordLinkKind::Reset, 'short');
            $this->fail('Expected a refusal.');
        } catch (UserRefused) {
        }

        $this->assertSame($user->id, $this->redeem($link, PasswordLinkKind::Reset));
    }

    public function test_a_user_with_an_unregistered_role_cannot_set_a_password(): void
    {
        $user = UserModel::factory()->create();
        $link = $this->issue($user, PasswordLinkKind::Reset);
        $user->newQuery()->whereKey($user->id)->toBase()->update(['role' => 'ghost']);

        $this->expectException(UserRefused::class);
        $this->redeem($link, PasswordLinkKind::Reset);
    }

    public function test_a_person_cannot_send_it(): void
    {
        $user = UserModel::factory()->create();

        $this->expectException(AuthorizationException::class);
        app(CommandBus::class)->send(new SetPasswordFromLinkCommand(Actor::user($user->id), 'a@b.pl', 'x', 'reset', 'brand-new-pass'));
    }

    public function test_a_link_issued_before_deactivation_no_longer_works(): void
    {
        $user = UserModel::factory()->create(['password' => 'old-password']);
        $link = $this->issue($user, PasswordLinkKind::Reset);
        $user->newQuery()->whereKey($user->id)->toBase()->update(['deactivated_at' => now()]);

        try {
            $this->redeem($link, PasswordLinkKind::Reset);
            $this->fail('Expected a refusal.');
        } catch (UserRefused) {
        }

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
