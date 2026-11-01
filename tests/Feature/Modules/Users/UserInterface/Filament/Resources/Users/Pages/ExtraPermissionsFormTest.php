<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\CreateUser;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\EditUser;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ViewUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-06, RULE-11 in the forms and the detail. */
class ExtraPermissionsFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_only_a_super_admin_sees_the_field_and_the_detail_section(): void
    {
        $target = UserModel::factory()->create(['extra_permissions' => ['users.impersonate']]);

        $this->actingAs(UserModel::factory()->admin()->create());
        Livewire::test(EditUser::class, ['record' => $target->getKey()])->assertFormFieldHidden('extra_permissions');
        Livewire::test(CreateUser::class)->assertFormFieldHidden('extra_permissions');
        Livewire::test(ViewUser::class, ['record' => $target->getKey()])->assertDontSee('Dodatkowe uprawnienia');

        $this->actingAs(UserModel::factory()->superAdmin()->create());
        Livewire::test(EditUser::class, ['record' => $target->getKey()])->assertFormFieldVisible('extra_permissions');
        Livewire::test(ViewUser::class, ['record' => $target->getKey()])
            ->assertSee(['Dodatkowe uprawnienia', 'Wchodzenie na konta innych użytkowników']);
    }

    public function test_a_super_admin_grants_and_revokes_in_the_edit_form(): void
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());
        $target = UserModel::factory()->admin()->create();

        Livewire::test(EditUser::class, ['record' => $target->getKey()])
            ->fillForm(['extra_permissions' => ['users.impersonate']])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(['users.impersonate'], $target->fresh()->extra_permissions);

        Livewire::test(EditUser::class, ['record' => $target->getKey()])
            ->fillForm(['extra_permissions' => []])
            ->call('save');
        $this->assertEmpty($target->fresh()->extra_permissions);
    }

    public function test_an_admin_saving_the_form_keeps_the_extra_permissions(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $target = UserModel::factory()->create(['extra_permissions' => ['users.impersonate']]);

        Livewire::test(EditUser::class, ['record' => $target->getKey()])
            ->fillForm(['name' => 'Inny'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['users.impersonate'], $target->fresh()->extra_permissions);
    }

    public function test_a_super_admin_creates_a_user_with_an_extra_permission(): void
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'role' => 'admin', 'name' => 'Nowy', 'email' => 'nowy@example.com',
                'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
                'extra_permissions' => ['users.impersonate'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(['users.impersonate'], UserModel::where('email', 'nowy@example.com')->firstOrFail()->extra_permissions);
    }
}
