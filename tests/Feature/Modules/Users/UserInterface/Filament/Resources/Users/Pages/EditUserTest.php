<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\EditUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class EditUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_an_admin_edits_a_user_and_an_empty_password_keeps_the_old_one(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $target = UserModel::factory()->create();

        Livewire::test(EditUser::class, ['record' => $target->getKey()])
            ->fillForm(['name' => 'Zmieniony', 'role' => 'admin', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $target->fresh();
        $this->assertSame('Zmieniony', $fresh->name);
        $this->assertSame('admin', $fresh->role);
        $this->assertTrue(Hash::check('password', $fresh->password));
    }

    public function test_the_role_field_is_hidden_when_editing_oneself_or_a_super_admin(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $super = UserModel::factory()->superAdmin()->create();
        $this->actingAs($super);

        Livewire::test(EditUser::class, ['record' => $super->getKey()])->assertFormFieldHidden('role');
        Livewire::test(EditUser::class, ['record' => $admin->getKey()])->assertFormFieldVisible('role');

        Livewire::test(EditUser::class, ['record' => UserModel::factory()->superAdmin()->create()->getKey()])
            ->assertFormFieldHidden('role');
    }

    public function test_an_admin_cannot_open_the_edit_page_of_a_super_admin(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $super = UserModel::factory()->superAdmin()->create();

        Livewire::test(EditUser::class, ['record' => $super->getKey()])->assertForbidden();
    }

    public function test_a_refusal_from_the_handler_keeps_the_form_open_and_changes_nothing(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $target = UserModel::factory()->create();
        UserModel::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(EditUser::class, ['record' => $target->getKey()])
            ->fillForm(['email' => 'taken@example.com', 'name' => 'Zmieniony'])
            ->call('save');

        $this->assertNotSame('Zmieniony', $target->fresh()->name);
    }

    public function test_there_is_no_delete_action(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $target = UserModel::factory()->create();

        Livewire::test(EditUser::class, ['record' => $target->getKey()])->assertActionDoesNotExist('delete');
    }
}
