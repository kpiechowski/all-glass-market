<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Pages;

use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\EditProfile;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\EditUser;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** WF-02, FR-14..FR-20 through "Mój profil". */
class EditProfileAccountTypeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Filament::setCurrentPanel('admin');
        CarbonImmutable::setTestNow('2026-11-01 10:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /** @return array<string, mixed> */
    private function formData(UserModel $user, array $overrides = []): array
    {
        return [...$user->attributesToArray(), 'phone' => '123456789', 'account_type' => $user->account_type?->value, ...$overrides];
    }

    public function test_a_user_becomes_company_with_the_declaration_and_the_control_locks(): void
    {
        $user = UserModel::factory()->create();
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->set('data', $this->formData($user, [
                'account_type' => 'company', 'company_nip' => '5252525252', 'company_address' => 'ul. Szklana 1',
                'seller_declaration_accepted' => true,
            ]))
            ->call('save')
            ->assertNotified(__('users::profile.notifications.saved'));

        $this->assertSame(AccountType::Company, $user->fresh()->account_type);

        $this->actingAs($user->fresh());

        Livewire::test(EditProfile::class)
            ->assertSee('Zmiana rodzaju konta jest zablokowana do: 11:30')
            ->assertFormFieldDisabled('account_type');
    }

    public function test_company_without_the_declaration_is_not_saved(): void
    {
        $user = UserModel::factory()->create();
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->set('data', $this->formData($user, [
                'account_type' => 'company', 'company_nip' => '5252525252', 'company_address' => 'ul. Szklana 1',
                'seller_declaration_accepted' => false,
            ]))
            ->call('save')
            ->assertHasErrors();

        $this->assertSame(AccountType::Private, $user->fresh()->account_type);
        $this->assertNull($user->fresh()->account_type_changed_at);
    }

    public function test_the_phone_is_required(): void
    {
        $user = UserModel::factory()->create();
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->set('data', $this->formData($user, ['phone' => '']))
            ->call('save')
            ->assertHasErrors(['data.phone']);
    }

    public function test_choosing_private_shows_the_remember_notice_modal(): void
    {
        $user = UserModel::factory()->company()->create();
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->fillForm(['account_type' => 'private'])
            ->assertActionMounted('privateAccountNotice');
    }

    public function test_the_badge_shows_until_the_user_is_company(): void
    {
        $this->actingAs(UserModel::factory()->create());
        $this->assertSame('i', EditProfile::getNavigationBadge());

        $this->actingAs(UserModel::factory()->company()->create());
        $this->assertNull(EditProfile::getNavigationBadge());
    }

    public function test_the_admin_form_has_no_account_type_control(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $target = UserModel::factory()->create();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $target->id])
            ->assertFormFieldDoesNotExist('account_type')
            ->assertFormFieldDoesNotExist('seller_declaration_accepted');
    }

    public function test_becoming_company_without_nip_or_address_is_refused_on_those_fields(): void
    {
        $user = UserModel::factory()->create();
        $this->actingAs($user);

        Livewire::test(EditProfile::class)
            ->set('data', $this->formData($user, [
                'account_type' => 'company', 'company_nip' => '', 'company_address' => '', 'seller_declaration_accepted' => true,
            ]))
            ->call('save')
            ->assertHasFormErrors(['company_nip', 'company_address']);

        $this->assertSame(AccountType::Private, $user->fresh()->account_type);
    }
}
