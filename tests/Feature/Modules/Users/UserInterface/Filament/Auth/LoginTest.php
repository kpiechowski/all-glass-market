<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Auth;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Auth\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

/** WF-01, FR-07..FR-10, FR-12, RULE-13. */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Filament::setCurrentPanel('admin');
        RateLimiter::clear('panel-login:seller@example.com|127.0.0.1');
    }

    private function seller(array $attributes = []): UserModel
    {
        return UserModel::factory()->create($attributes + ['email' => 'seller@example.com', 'password' => 'correct-pass']);
    }

    private function signIn(string $email, string $password)
    {
        return Livewire::test(Login::class)->fillForm(['email' => $email, 'password' => $password])->call('authenticate');
    }

    public function test_a_user_signs_in_with_email_and_password(): void
    {
        $user = $this->seller();

        $this->signIn('seller@example.com', 'correct-pass')->assertHasNoFormErrors()->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_is_refused_with_a_generic_message(): void
    {
        $this->seller();

        $this->signIn('seller@example.com', 'wrong-pass')->assertHasErrors(['data.email' => __('users::login.failed')]);

        $this->assertGuest();
    }

    public function test_an_unknown_email_gets_the_same_message(): void
    {
        $this->signIn('nobody@example.com', 'whatever-pass')->assertHasErrors(['data.email' => __('users::login.failed')]);
    }

    public function test_the_sixth_attempt_within_a_minute_is_refused_even_with_the_right_password(): void
    {
        $this->seller();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->signIn('seller@example.com', 'wrong-pass')->assertHasErrors(['data.email' => __('users::login.failed')]);
        }

        $component = $this->signIn('seller@example.com', 'correct-pass');

        $component->assertHasErrors('data.email');
        $this->assertStringContainsString('Spróbuj ponownie za', $component->errors()->first('data.email'));
        $this->assertGuest();
    }

    public function test_the_limit_is_per_email_and_ip(): void
    {
        $this->seller();
        UserModel::factory()->create(['email' => 'other@example.com', 'password' => 'other-pass']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->signIn('seller@example.com', 'wrong-pass');
        }

        $this->signIn('other@example.com', 'other-pass')->assertHasNoFormErrors();

        RateLimiter::clear('panel-login:other@example.com|127.0.0.1');
    }

    public function test_a_user_whose_role_is_not_registered_is_refused(): void
    {
        $this->seller(['role' => 'ghost-module-role']);

        $this->signIn('seller@example.com', 'correct-pass')->assertHasErrors(['data.email' => __('users::login.no_valid_role')]);

        $this->assertGuest();
    }

    public function test_an_admin_only_account_is_admitted(): void
    {
        $this->seller(['role' => 'admin']);

        $this->signIn('seller@example.com', 'correct-pass')->assertHasNoFormErrors();

        $this->assertAuthenticated();
    }

    public function test_the_panel_refuses_a_session_whose_role_is_not_registered(): void
    {
        $this->actingAs(UserModel::factory()->create(['role' => 'ghost-module-role']));

        $this->get('/admin')->assertForbidden();
    }

    public function test_the_root_and_the_plain_sign_in_address_lead_to_the_panel_sign_in_page(): void
    {
        $loginUrl = Filament::getPanel('admin')->getLoginUrl();

        $this->get('/')->assertRedirect($loginUrl);
        $this->get('/login')->assertRedirect($loginUrl);
    }

    public function test_the_sign_in_page_is_not_indexed(): void
    {
        $this->get(Filament::getPanel('admin')->getLoginUrl())->assertOk()->assertSee('name="robots" content="noindex', false);
    }

    public function test_a_guest_is_sent_to_sign_in_from_a_panel_page(): void
    {
        $this->get('/admin')->assertRedirect(Filament::getPanel('admin')->getLoginUrl());
    }

    public function test_nobody_can_register(): void
    {
        $this->assertFalse(Filament::getPanel('admin')->hasRegistration());
        $this->get('/register')->assertNotFound();
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_sessions_last_120_minutes_and_survive_closing_the_browser(): void
    {
        $this->assertSame(120, config('session.lifetime'));
        $this->assertFalse(config('session.expire_on_close'));
    }

    public function test_a_deactivated_user_is_refused_with_the_inactive_message(): void
    {
        $this->seller(['deactivated_at' => now()]);

        $this->signIn('seller@example.com', 'correct-pass')->assertHasErrors(['data.email' => __('users::login.deactivated')]);

        $this->assertGuest();
    }

    public function test_the_panel_ends_the_session_of_a_user_deactivated_mid_session(): void
    {
        $user = UserModel::factory()->admin()->create();
        $this->actingAs($user)->get('/admin')->assertOk();

        DB::table('users')->where('id', $user->id)->update(['deactivated_at' => now()]);
        $this->app['auth']->forgetGuards();

        $this->actingAs($user->fresh())->get('/admin')->assertRedirect(Filament::getPanel('admin')->getLoginUrl());
        $this->assertGuest();
    }
}
