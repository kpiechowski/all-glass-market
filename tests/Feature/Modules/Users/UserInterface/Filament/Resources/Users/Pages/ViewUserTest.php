<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ViewUser;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_the_detail_shows_contact_company_account_type_role_and_shipping_text(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $target = UserModel::factory()->create([
            'name' => 'Anna Nowak', 'phone' => '600700800', 'company_name' => 'Szklarnia', 'company_nip' => '1234567890',
            'shipment_address' => 'Magazyn 5', 'city' => 'Łódź', 'city_code' => '90-001', 'shipping_note' => '<p>Kurier</p>',
        ]);

        Livewire::test(ViewUser::class, ['record' => $target->getKey()])
            ->assertSee(['Anna Nowak', '600700800', 'Szklarnia', '1234567890', 'Magazyn 5', 'Łódź', '90-001', 'Kurier', 'Prywatne', 'Użytkownik']);
    }

    public function test_an_admin_sees_a_super_admin_read_only(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $super = UserModel::factory()->superAdmin()->create();

        Livewire::test(ViewUser::class, ['record' => $super->getKey()])
            ->assertSuccessful()
            ->assertActionHidden('edit');
    }

    public function test_an_admin_deactivates_and_reactivates_a_user_from_the_detail_page(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $target = UserModel::factory()->create();

        Livewire::test(ViewUser::class, ['record' => $target->getKey()])
            ->assertActionVisible('deactivate')
            ->assertActionHidden('reactivate')
            ->callAction('deactivate')
            ->assertNotified();

        $this->assertFalse($target->fresh()->isActive());

        Livewire::test(ViewUser::class, ['record' => $target->getKey()])
            ->assertActionHidden('deactivate')
            ->callAction('reactivate')
            ->assertNotified();

        $this->assertTrue($target->fresh()->isActive());
    }

    public function test_deactivation_is_not_offered_for_oneself_or_a_super_admin(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $super = UserModel::factory()->superAdmin()->create();
        $otherSuper = UserModel::factory()->superAdmin()->create();

        $this->actingAs($admin);
        Livewire::test(ViewUser::class, ['record' => $admin->getKey()])->assertActionHidden('deactivate');
        Livewire::test(ViewUser::class, ['record' => $super->getKey()])->assertActionHidden('deactivate');

        $this->actingAs($super);
        Livewire::test(ViewUser::class, ['record' => $otherSuper->getKey()])->assertActionHidden('deactivate');
        Livewire::test(ViewUser::class, ['record' => $super->getKey()])->assertActionHidden('deactivate');
    }

    public function test_a_company_shows_the_declaration_date_and_version_and_every_user_shows_the_active_state(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $company = UserModel::factory()->company()->create([
            'seller_declaration_accepted_at' => '2026-03-04 10:00:00', 'seller_declaration_version' => 'V1',
        ]);
        $private = UserModel::factory()->create();

        Livewire::test(ViewUser::class, ['record' => $company->getKey()])
            ->assertSee([__('users::resources.user.fields.declaration_accepted_at'), '2026', __('users::resources.user.fields.declaration_version'), 'V1', __('users::resources.user.status.active')]);

        Livewire::test(ViewUser::class, ['record' => $private->getKey()])
            ->assertSee(__('users::resources.user.status.active'))
            ->assertDontSee(__('users::resources.user.fields.declaration_version'));
    }

    public function test_the_header_keeps_edit_outside_and_the_dangerous_actions_inside_the_menu(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $target = UserModel::factory()->passwordChangeRequired()->create();

        $page = Livewire::test(ViewUser::class, ['record' => $target->getKey()])
            ->assertSee(__('users::resources.user.markers.password_change_required'));

        $topLevel = collect($page->instance()->getCachedHeaderActions())
            ->map(fn ($action): ?string => $action instanceof ActionGroup ? null : $action->getName())
            ->filter()
            ->values()
            ->all();

        $this->assertSame(['edit', 'reactivate'], $topLevel);
        $page->assertActionVisible('deactivate')->assertActionVisible('set_password');
    }
}
