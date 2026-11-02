<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Persistence;

use App\Modules\Users\Domain\Entities\User;
use App\Modules\Users\Domain\Repositories\UserRepository;
use App\Modules\Users\Domain\ValueObjects\Email;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_gets_and_saves_a_user(): void
    {
        $users = app(UserRepository::class);

        $id = $users->add(User::create('Jan', Email::from('jan@example.com'), 'user', 'hash'));
        $user = $users->get($id);
        $user->assignRole('admin');
        $users->save($user);

        $this->assertSame('admin', $users->get($id)->roleKey());
        $this->assertSame('admin', UserModel::findOrFail($id)->role);
        $this->assertNull($users->find(9999));
    }

    public function test_it_knows_whether_a_user_exists(): void
    {
        $model = UserModel::factory()->create();

        $this->assertTrue(app(UserRepository::class)->exists($model->id));
        $this->assertFalse(app(UserRepository::class)->exists($model->id + 100));
    }

    public function test_it_finds_a_user_by_email(): void
    {
        $model = UserModel::factory()->create(['email' => 'a@example.com']);

        $this->assertSame($model->id, app(UserRepository::class)->findByEmail('A@example.com')?->id());
        $this->assertNull(app(UserRepository::class)->findByEmail('missing@example.com'));
    }

    public function test_it_knows_a_taken_email_and_excludes_the_given_user(): void
    {
        $model = UserModel::factory()->create(['email' => 'a@example.com']);
        $users = app(UserRepository::class);

        $this->assertTrue($users->emailIsTaken('a@example.com'));
        $this->assertFalse($users->emailIsTaken('a@example.com', $model->id));
        $this->assertFalse($users->emailIsTaken('free@example.com'));
    }

    public function test_it_reads_a_role_key(): void
    {
        $model = UserModel::factory()->admin()->create();

        $this->assertSame('admin', app(UserRepository::class)->roleKeyOf($model->id));
        $this->assertNull(app(UserRepository::class)->roleKeyOf(9999));
    }

    public function test_it_lists_ids_with_the_given_roles_and_excludes_others(): void
    {
        $admin = UserModel::factory()->admin()->create();
        UserModel::factory()->create();

        $this->assertSame([$admin->id], app(UserRepository::class)->userIdsWithRoles(['admin', 'super_admin']));
    }
}
