<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Resources\Users;

use App\Core\Exceptions\ReadOnlyModelException;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\CreateUser;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\EditUser;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ListUsers;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ViewUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Support\CapturesPasswordLinks;
use Tests\TestCase;

/** FR-24, FR-26, FR-36, FR-39: the Admin's password tools and the markers. */
class PasswordManagementTest extends TestCase
{
    use CapturesPasswordLinks, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Filament::setCurrentPanel('admin');
        $this->actingAs(UserModel::factory()->admin()->create());
    }

    public function test_the_list_marks_users_who_must_change_their_password_and_filters_them(): void
    {
        $flagged = UserModel::factory()->withRole('user')->passwordChangeRequired()->create();
        $plain = UserModel::factory()->withRole('user')->create();

        Livewire::test(ListUsers::class)
            ->assertSee(__('users::resources.user.markers.password_change_required'))
            ->filterTable('password_change_required', true)
            ->assertCanSeeTableRecords([$flagged])
            ->assertCanNotSeeTableRecords([$plain])
            ->filterTable('password_change_required', false)
            ->assertCanSeeTableRecords([$plain])
            ->assertCanNotSeeTableRecords([$flagged]);
    }

    public function test_the_detail_shows_the_marker(): void
    {
        $flagged = UserModel::factory()->withRole('user')->passwordChangeRequired()->create();

        Livewire::test(ViewUser::class, ['record' => $flagged->getKey()])
            ->assertSee(__('users::resources.user.markers.password_change_required'));
    }

    public function test_an_admin_sets_and_clears_the_marker_from_the_detail(): void
    {
        $target = UserModel::factory()->withRole('user')->create();

        Livewire::test(ViewUser::class, ['record' => $target->getKey()])
            ->assertActionHidden('clear_password_change')
            ->callAction('require_password_change')
            ->assertActionHidden('require_password_change')
            ->callAction('clear_password_change');

        $this->assertFalse($target->fresh()->password_change_required);
    }

    public function test_an_admin_re_sends_links_from_the_edit_page_while_email_works(): void
    {
        $this->emailSendingWorks();
        $target = UserModel::factory()->withRole('user')->create();

        Livewire::test(EditUser::class, ['record' => $target->getKey()])
            ->callAction('send_invitation_link')
            ->callAction('send_reset_link');

        $this->sentLink($target, PasswordLinkKind::Invitation);
        $this->sentLink($target, PasswordLinkKind::Reset);
    }

    public function test_link_actions_are_hidden_while_email_sending_is_unavailable(): void
    {
        $target = UserModel::factory()->withRole('user')->create();

        Livewire::test(ViewUser::class, ['record' => $target->getKey()])
            ->assertActionHidden('send_invitation_link')
            ->assertActionHidden('send_reset_link');
    }

    public function test_the_actions_are_hidden_for_a_super_admin_target(): void
    {
        $this->emailSendingWorks();
        $super = UserModel::factory()->superAdmin()->create();

        Livewire::test(ViewUser::class, ['record' => $super->getKey()])
            ->assertActionHidden('send_reset_link')
            ->assertActionHidden('require_password_change');
    }

    public function test_the_create_form_offers_an_invitation_only_while_email_works(): void
    {
        Notification::fake();
        Livewire::test(CreateUser::class)->assertFormFieldHidden('password_method');

        $this->emailSendingWorks();
        Livewire::test(CreateUser::class)->assertFormFieldVisible('password_method');
    }

    public function test_an_invitation_creates_the_user_without_a_password_and_sends_the_link(): void
    {
        $this->emailSendingWorks();

        Livewire::test(CreateUser::class)
            ->fillForm(['role' => 'user', 'name' => 'Zaproszony', 'email' => 'invited@example.com', 'password_method' => 'invitation'])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = UserModel::where('email', 'invited@example.com')->firstOrFail();
        $this->assertFalse($created->password_change_required);
        $this->sentLink($created, PasswordLinkKind::Invitation);
    }

    public function test_a_temporary_password_sets_the_marker_when_created_from_the_form(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'role' => 'user', 'name' => 'Tymczasowy', 'email' => 'temp@example.com',
                'password' => 'temporary-pass', 'password_confirmation' => 'temporary-pass',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(UserModel::where('email', 'temp@example.com')->firstOrFail()->password_change_required);
    }

    public function test_an_admins_new_password_from_the_set_password_action_sets_the_marker(): void
    {
        $target = UserModel::factory()->withRole('user')->create();

        Livewire::test(EditUser::class, ['record' => $target->getKey()])
            ->callAction('set_password', data: ['password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass'])
            ->assertHasNoActionErrors()
            ->assertNotified(__('users::resources.user.actions.set_password.done'));

        $this->assertTrue(Hash::check('brand-new-pass', $target->fresh()->password));
        $this->assertTrue($target->fresh()->password_change_required);
    }

    public function test_the_edit_form_has_no_password_section(): void
    {
        $target = UserModel::factory()->withRole('user')->create();

        Livewire::test(EditUser::class, ['record' => $target->getKey()])
            ->assertFormFieldHidden('password');
    }

    public function test_the_model_refuses_a_direct_password_write(): void
    {
        $user = UserModel::factory()->create();

        $this->expectException(ReadOnlyModelException::class);

        $user->forceFill(['password' => 'sneaky-pass'])->save();
    }

    public function test_the_model_still_allows_the_sign_in_remember_token(): void
    {
        $user = UserModel::factory()->create();

        $user->setRememberToken('token-123');

        $this->assertTrue($user->save());
        $this->assertSame('token-123', $user->fresh()->getRememberToken());
    }
}
