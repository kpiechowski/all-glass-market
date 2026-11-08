<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Http;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\Commands\ChangeUserPassword\ChangeUserPasswordCommand;
use App\Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\EditProfile;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\EditUser;
use Ecotone\Modelling\CommandBus;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-10: a password change ends the User's other sessions, whichever path changed it. */
class PasswordChangeEndsOtherSessionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function sessionHashOf(UserModel $user): string
    {
        return Auth::guard()->hashPasswordForCookie($user->getAuthPassword());
    }

    private function requestAsOtherSession(UserModel $user, string $sessionHash)
    {
        // A second browser: a fresh request whose session carries the hash from sign-in time.
        $this->app['auth']->forgetGuards();

        return $this->withSession(['password_hash_web' => $sessionHash])->actingAs($user->fresh())->get('/admin');
    }

    public function test_the_other_session_works_while_the_password_is_unchanged(): void
    {
        $user = UserModel::factory()->create();

        $this->requestAsOtherSession($user, $this->sessionHashOf($user))->assertOk();
    }

    public function test_change_user_password_ends_the_other_sessions(): void
    {
        $user = UserModel::factory()->create();
        $oldHash = $this->sessionHashOf($user);

        app(CommandBus::class)->send(new ChangeUserPasswordCommand(Actor::user($user->id), $user->id, 'brand-new-pass'));

        $this->requestAsOtherSession($user, $oldHash)->assertRedirect(Filament::getPanel('admin')->getLoginUrl());
        $this->assertGuest();
    }

    public function test_an_admin_changing_a_users_password_ends_that_users_sessions(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create();
        $oldHash = $this->sessionHashOf($user);

        app(CommandBus::class)->send(new ChangeUserPasswordCommand(Actor::user($admin->id), $user->id, 'brand-new-pass'));

        $this->requestAsOtherSession($user, $oldHash)->assertRedirect(Filament::getPanel('admin')->getLoginUrl());
    }

    public function test_update_user_with_a_password_ends_the_other_sessions(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create();
        $oldHash = $this->sessionHashOf($user);

        app(CommandBus::class)->send(new UpdateUserCommand(
            actor: Actor::user($admin->id), userId: $user->id, name: $user->name, email: $user->email,
            phone: null, companyName: null, companyNip: null, companyAddress: null, pickupAddress: null,
            city: null, postalCode: null, shippingText: null, password: 'brand-new-pass',
        ));

        $this->requestAsOtherSession($user, $oldHash)->assertRedirect(Filament::getPanel('admin')->getLoginUrl());
    }

    public function test_an_update_without_a_password_keeps_the_other_sessions(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create();
        $oldHash = $this->sessionHashOf($user);

        app(CommandBus::class)->send(new UpdateUserCommand(
            actor: Actor::user($admin->id), userId: $user->id, name: 'Renamed', email: $user->email,
            phone: null, companyName: null, companyNip: null, companyAddress: null, pickupAddress: null,
            city: null, postalCode: null, shippingText: null,
        ));

        $this->requestAsOtherSession($user, $oldHash)->assertOk();
    }

    public function test_the_session_that_changes_the_password_on_the_profile_page_stays_signed_in(): void
    {
        Filament::setCurrentPanel('admin');
        $user = UserModel::factory()->create();
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->callAction('changePassword', data: ['password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])
            ->assertHasNoActionErrors();

        $this->assertSame(
            $this->sessionHashOf($user->fresh()),
            session('password_hash_web'),
        );
    }

    public function test_an_admin_who_changes_their_own_password_on_the_edit_page_stays_signed_in(): void
    {
        Filament::setCurrentPanel('admin');
        $admin = UserModel::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getKey()])
            ->callAction('set_password', data: ['password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])
            ->assertHasNoActionErrors();

        $this->assertSame($this->sessionHashOf($admin->fresh()), session('password_hash_web'));
    }
}
