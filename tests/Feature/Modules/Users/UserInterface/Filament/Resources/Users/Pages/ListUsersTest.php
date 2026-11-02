<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ListUsers;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_an_admin_sees_users_searches_and_filters_them(): void
    {
        $admin = UserModel::factory()->admin()->create(['name' => 'Ada Admin']);
        $anna = UserModel::factory()->create(['name' => 'Anna Nowak', 'email' => 'anna@example.com']);
        $jan = UserModel::factory()->create(['name' => 'Jan Kowalski']);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$admin, $anna, $jan])
            ->searchTable('anna@example.com')
            ->assertCanSeeTableRecords([$anna])
            ->assertCanNotSeeTableRecords([$jan])
            ->searchTable('')
            ->filterTable('role', 'admin')
            ->assertCanSeeTableRecords([$admin])
            ->assertCanNotSeeTableRecords([$anna, $jan])
            ->removeTableFilters()
            ->filterTable('account_type', 'company')
            ->assertCanNotSeeTableRecords([$anna, $jan, $admin]);
    }

    public function test_an_unknown_role_shows_as_nieznana_rola_instead_of_failing(): void
    {
        $admin = UserModel::factory()->admin()->create();
        UserModel::factory()->withRole('removed_module_role')->create();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)->assertSee('nieznana rola');
    }

    public function test_a_user_without_the_permission_cannot_open_the_list(): void
    {
        $this->actingAs(UserModel::factory()->create());

        Livewire::test(ListUsers::class)->assertForbidden();
    }

    public function test_the_list_marks_deactivated_users_and_filters_by_activity(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $inactive = UserModel::factory()->deactivated()->create();
        $active = UserModel::factory()->create();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertSee('konto nieaktywne')
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$inactive])
            ->assertCanNotSeeTableRecords([$active, $admin])
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$active, $admin])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_an_admin_gets_no_edit_action_on_the_row_of_a_super_admin(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $super = UserModel::factory()->superAdmin()->create();
        $plain = UserModel::factory()->create();

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('edit', $super)
            ->assertTableActionVisible('edit', $plain);
    }
}
