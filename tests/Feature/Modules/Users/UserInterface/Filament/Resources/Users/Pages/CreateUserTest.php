<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Resources\Users\Pages;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\CreateUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->actingAs(UserModel::factory()->admin()->create());
    }

    public function test_it_creates_a_user_through_the_command(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'role' => 'user',
                'name' => 'Nowy Użytkownik',
                'email' => 'nowy@example.com',
                'password' => 'temporary-pass',
                'password_confirmation' => 'temporary-pass',
                'shipping_note' => '<p>Wysyłka</p>',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = UserModel::where('email', 'nowy@example.com')->firstOrFail();
        $this->assertSame('user', $created->role);
        $this->assertSame('private', $created->account_type->value);
        $this->assertStringContainsString('Wysyłka', $created->shipping_note);
    }

    public function test_the_role_select_never_offers_super_admin(): void
    {
        Livewire::test(CreateUser::class)
            ->assertFormFieldExists('role', fn ($field): bool => array_keys($field->getOptions()) === ['admin', 'user'] || array_keys($field->getOptions()) === ['user', 'admin'])
            ->fillForm(['role' => 'super_admin', 'name' => 'X', 'email' => 'x@example.com', 'password' => 'temporary-pass', 'password_confirmation' => 'temporary-pass'])
            ->call('create')
            ->assertHasFormErrors(['role']);

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    public function test_a_duplicate_email_is_a_field_error(): void
    {
        UserModel::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(CreateUser::class)
            ->fillForm(['role' => 'user', 'name' => 'X', 'email' => 'taken@example.com', 'password' => 'temporary-pass', 'password_confirmation' => 'temporary-pass'])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    public function test_a_duplicate_email_shows_the_polish_validation_message(): void
    {
        UserModel::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(CreateUser::class)
            ->fillForm(['role' => 'user', 'name' => 'X', 'email' => 'taken@example.com', 'password' => 'temporary-pass', 'password_confirmation' => 'temporary-pass'])
            ->call('create')
            ->assertSee('Ta wartość w polu adres e-mail jest już zajęta.')
            ->assertDontSee('validation.unique');
    }

    public function test_the_password_generator_is_labelled_in_polish(): void
    {
        Livewire::test(CreateUser::class)
            ->assertSee('Wygeneruj hasło')
            ->assertDontSee('Generate Password');
    }

    public function test_the_create_page_title_is_in_polish(): void
    {
        Livewire::test(CreateUser::class)
            ->assertSee('Utwórz użytkownika')
            ->assertDontSee('Utwórz Użytkownik');
    }
}
