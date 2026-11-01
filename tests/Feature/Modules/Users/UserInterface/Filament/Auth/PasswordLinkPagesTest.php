<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Auth;

use App\Modules\Users\Domain\Contracts\PasswordLinks;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Infrastructure\Notifications\PasswordLinkNotification;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Auth\AcceptInvitation;
use App\Modules\Users\UserInterface\Filament\Auth\RequestPasswordReset;
use App\Modules\Users\UserInterface\Filament\Auth\ResetPassword;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\Support\CapturesPasswordLinks;
use Tests\TestCase;

/** WF-03 and the invitation path of WF-04, FR-11, RULE-14. */
class PasswordLinkPagesTest extends TestCase
{
    use CapturesPasswordLinks, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Filament::setCurrentPanel('admin');
    }

    private function asksForReset(string $email)
    {
        return Livewire::test(RequestPasswordReset::class)->fillForm(['email' => $email])->call('request');
    }

    private function setsPassword(string $page, array $link, string $password = 'brand-new-pass')
    {
        return Livewire::test($page, ['email' => $link['email'], 'token' => $link['token']])
            ->fillForm(['password' => $password, 'passwordConfirmation' => $password])
            ->call('resetPassword');
    }

    /** @return array{url: string, email: string, token: string} */
    private function linkFor(UserModel $user, PasswordLinkKind $kind): array
    {
        $this->emailSendingWorks();
        app(PasswordLinks::class)->send($user->id, $kind);

        return $this->sentLink($user, $kind);
    }

    public function test_the_sign_in_page_links_to_the_reset_request(): void
    {
        $this->get(Filament::getPanel('admin')->getLoginUrl())
            ->assertOk()
            ->assertSee(Filament::getPanel('admin')->getRequestPasswordResetUrl(), false);
    }

    public function test_a_known_email_gets_the_neutral_answer_and_a_link(): void
    {
        $this->emailSendingWorks();
        $user = UserModel::factory()->withRole('user')->create(['email' => 'jan@example.com']);

        $this->asksForReset('jan@example.com')->assertNotified(__('users::login.reset_requested.title'));

        Notification::assertSentToTimes($user, PasswordLinkNotification::class, 1);
    }

    public function test_an_unknown_email_gets_the_same_answer_and_no_link(): void
    {
        $this->emailSendingWorks();

        $this->asksForReset('nobody@example.com')->assertNotified(__('users::login.reset_requested.title'));

        Notification::assertNothingSent();
    }

    public function test_the_reset_page_sets_the_password_and_signs_the_user_in(): void
    {
        $user = UserModel::factory()->withRole('user')->passwordChangeRequired()->create(['password' => 'old-password']);
        $link = $this->linkFor($user, PasswordLinkKind::Reset);

        $this->setsPassword(ResetPassword::class, $link)->assertRedirect(Filament::getUrl());

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
        $this->assertFalse($user->fresh()->password_change_required);
    }

    public function test_the_signed_reset_url_opens_the_page(): void
    {
        $user = UserModel::factory()->withRole('user')->create();
        $link = $this->linkFor($user, PasswordLinkKind::Reset);

        $this->get($link['url'])->assertOk();
        $this->get(preg_replace('/signature=[^&]+/', 'signature=bad', $link['url']))->assertForbidden();
    }

    public function test_a_used_link_is_refused_and_signs_nobody_in(): void
    {
        $user = UserModel::factory()->withRole('user')->create(['password' => 'old-password']);
        $link = $this->linkFor($user, PasswordLinkKind::Reset);
        $this->setsPassword(ResetPassword::class, $link);
        $this->app['auth']->guard()->logout();
        RateLimiter::clear('filament:auth:reset_password:rate_limit:'.hash('sha256', 'users|'.$link['email']));

        $second = $this->setsPassword(ResetPassword::class, $link, 'another-new-pass');

        $second->assertNotified(__('users::exceptions.password_link_invalid'));
        $this->assertGuest();
        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_an_expired_link_is_refused(): void
    {
        $user = UserModel::factory()->withRole('user')->create(['password' => 'old-password']);
        $link = $this->linkFor($user, PasswordLinkKind::Reset);

        $this->travel(61)->minutes();

        $this->setsPassword(ResetPassword::class, $link)->assertNotified(__('users::exceptions.password_link_invalid'));
        $this->assertGuest();
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_the_invitation_page_sets_the_password_and_signs_the_user_in(): void
    {
        $user = UserModel::factory()->withRole('user')->create();
        $link = $this->linkFor($user, PasswordLinkKind::Invitation);

        $this->get($link['url'])->assertOk();
        $this->setsPassword(AcceptInvitation::class, $link)->assertRedirect(Filament::getUrl());

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
    }

    public function test_an_invitation_link_does_not_open_as_a_reset(): void
    {
        $user = UserModel::factory()->withRole('user')->create(['password' => 'old-password']);
        $link = $this->linkFor($user, PasswordLinkKind::Invitation);

        $this->setsPassword(ResetPassword::class, $link)->assertNotified(__('users::exceptions.password_link_invalid'));
        $this->assertGuest();
    }
}
