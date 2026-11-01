<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\CreateUser;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Commands\CreateUser\CreateUserCommand;
use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function create(Actor $actor, string $role = 'user', string $email = 'new@example.com', string $password = 'secret-pass', string $name = 'Nowy'): int
    {
        return app(CommandBus::class)->send(new CreateUserCommand(
            $actor, $role, $name, $email, $password, phone: '123', companyName: 'Firma', shippingText: '<p>x</p>',
        ));
    }

    public function test_an_admin_creates_a_private_user_with_a_hashed_temporary_password(): void
    {
        $admin = UserModel::factory()->admin()->create();

        $id = $this->create(Actor::user($admin->id));

        $created = UserModel::findOrFail($id);
        $this->assertSame('user', $created->role);
        $this->assertSame(AccountType::Private, $created->account_type);
        $this->assertSame('Firma', $created->company_name);
        $this->assertSame('<p>x</p>', $created->shipping_note);
        $this->assertTrue(Hash::check('secret-pass', $created->password));
    }

    public function test_an_admin_may_create_an_admin(): void
    {
        $admin = UserModel::factory()->admin()->create();

        $this->assertSame('admin', UserModel::findOrFail($this->create(Actor::user($admin->id), 'admin'))->role);
    }

    public function test_a_user_without_the_permission_is_rejected(): void
    {
        $seller = UserModel::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->create(Actor::user($seller->id));
    }

    public function test_super_admin_is_never_assignable_even_by_a_super_admin(): void
    {
        $super = UserModel::factory()->superAdmin()->create();

        $this->expectException(UserRefused::class);
        $this->create(Actor::user($super->id), 'super_admin');
    }

    public function test_an_unregistered_role_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();

        $this->expectException(UserRefused::class);
        $this->create(Actor::user($admin->id), 'ghost');
    }

    public function test_a_duplicate_email_is_refused_and_nothing_is_created(): void
    {
        $admin = UserModel::factory()->admin()->create();
        UserModel::factory()->create(['email' => 'new@example.com']);

        try {
            $this->create(Actor::user($admin->id));
            $this->fail('A duplicate e-mail was accepted');
        } catch (UserRefused) {
            $this->assertSame(2, UserModel::count());
        }
    }

    public function test_a_malformed_email_a_short_password_and_a_blank_name_are_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $actor = Actor::user($admin->id);

        foreach ([['email' => 'nope'], ['password' => 'short'], ['name' => ' ']] as $override) {
            try {
                $this->create($actor, ...$override);
                $this->fail('Invalid input was accepted');
            } catch (UserRefused) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame(1, UserModel::count());
    }

    public function test_two_users_may_share_a_name(): void
    {
        $admin = UserModel::factory()->admin()->create(['name' => 'Jan_Dup']);

        $this->assertGreaterThan(0, $this->create(Actor::user($admin->id), name: 'Jan_Dup'));
    }
}
