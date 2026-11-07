<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ListUsers;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ViewUser;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Actions\ActionGroup;
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

    public function test_the_list_tabs_split_active_and_inactive_users_and_all_marks_the_inactive_ones(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $inactive = UserModel::factory()->deactivated()->create();
        $active = UserModel::factory()->create();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$active, $admin])
            ->assertCanNotSeeTableRecords([$inactive])
            ->set('activeTab', 'inactive')
            ->assertCanSeeTableRecords([$inactive])
            ->assertCanNotSeeTableRecords([$active, $admin])
            ->set('activeTab', 'all')
            ->assertCanSeeTableRecords([$inactive, $active, $admin])
            ->assertSee(__('users::resources.user.status.inactive'));
    }

    public function test_the_row_opens_the_record_view(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $target = UserModel::factory()->create();

        $table = Livewire::test(ListUsers::class)->instance()->getTable();

        $this->assertSame(UserResource::getUrl('view', ['record' => $target]), $table->getRecordUrl($target));
    }

    public function test_a_super_admin_gets_no_edit_on_another_super_admin_but_row_and_detail_stay_read_only(): void
    {
        $me = UserModel::factory()->superAdmin()->create();
        $this->actingAs($me);
        $other = UserModel::factory()->superAdmin()->create();

        Livewire::test(ListUsers::class)
            ->assertSuccessful();

        $this->assertNotContains('edit', $this->visibleRowActions($other));
        $this->assertContains('edit', $this->visibleRowActions($me));

        Livewire::test(ViewUser::class, ['record' => $other->getKey()])
            ->assertActionHidden('edit')
            ->assertActionHidden('send_reset_link')
            ->assertActionHidden('require_password_change');
    }

    public function test_record_actions_sit_inside_one_action_group(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());

        $actions = Livewire::test(ListUsers::class)->instance()->getTable()->getRecordActions();

        $this->assertCount(1, $actions);
        $this->assertInstanceOf(ActionGroup::class, $actions[0]);
    }

    public function test_an_admin_gets_no_edit_action_on_the_row_of_a_super_admin(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $super = UserModel::factory()->superAdmin()->create();
        $plain = UserModel::factory()->create();

        $this->assertNotContains('edit', $this->visibleRowActions($super));
        $this->assertContains('edit', $this->visibleRowActions($plain));
    }

    /** @return list<string> */
    private function visibleRowActions(UserModel $record): array
    {
        $group = Livewire::test(ListUsers::class)->instance()->getTable()->getRecordActions()[0];

        return collect($group->getActions())
            ->each(fn ($action) => $action->record($record))
            ->filter(fn ($action): bool => $action->isVisible())
            ->map(fn ($action): string => $action->getName())
            ->values()
            ->all();
    }
}
