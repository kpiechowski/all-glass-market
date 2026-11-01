<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Auth;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Auth\SetNewPassword;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/** WF-07, FR-37, FR-38. */
class SetNewPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Filament::setCurrentPanel('admin');
    }

    private function setPassword(string $password, ?string $confirmation = null)
    {
        return Livewire::test(SetNewPassword::class)
            ->fillForm(['password' => $password, 'password_confirmation' => $confirmation ?? $password])
            ->call('setNewPassword');
    }

    public function test_the_user_sets_a_new_password_and_lands_on_the_dashboard(): void
    {
        $user = UserModel::factory()->withRole('user')->passwordChangeRequired()->create(['password' => 'temporary-pass']);
        $this->actingAs($user);

        $this->setPassword('brand-new-pass')->assertHasNoFormErrors()->assertRedirect(Filament::getUrl());

        $this->assertTrue(Hash::check('brand-new-pass', $user->fresh()->password));
        $this->assertFalse($user->fresh()->password_change_required);
    }

    public function test_the_changing_session_keeps_working_after_the_change(): void
    {
        $user = UserModel::factory()->withRole('user')->passwordChangeRequired()->create();
        $this->actingAs($user);

        $this->setPassword('brand-new-pass');

        $this->assertSame(
            Auth::guard()->hashPasswordForCookie($user->fresh()->getAuthPassword()),
            session('password_hash_web'),
        );
    }

    public function test_the_current_password_is_refused_as_the_new_one(): void
    {
        $user = UserModel::factory()->withRole('user')->passwordChangeRequired()->create(['password' => 'temporary-pass']);
        $this->actingAs($user);

        $this->setPassword('temporary-pass')->assertNotified(__('users::exceptions.password_same_as_current'));

        $this->assertTrue($user->fresh()->password_change_required);
    }

    public function test_rule_03_a_short_or_unconfirmed_password_is_a_field_error(): void
    {
        $this->actingAs(UserModel::factory()->withRole('user')->passwordChangeRequired()->create());

        $this->setPassword('short')->assertHasFormErrors(['password']);
        $this->setPassword('long-enough-pass', 'different-pass')->assertHasFormErrors(['password']);
    }

    public function test_a_user_without_the_marker_is_sent_to_the_dashboard(): void
    {
        $this->actingAs(UserModel::factory()->withRole('user')->create());

        Livewire::test(SetNewPassword::class)->assertRedirect(Filament::getUrl());
    }
}
