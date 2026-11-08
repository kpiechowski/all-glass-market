<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\ModuleApi;

use App\Modules\Users\Application\ModuleApi\UserEmails;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEmailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_gives_the_email_of_a_user(): void
    {
        $user = UserModel::factory()->create(['email' => 'jan@example.com']);

        $this->assertSame('jan@example.com', app(UserEmails::class)->emailOf($user->id));
    }

    public function test_it_gives_null_for_an_unknown_user(): void
    {
        $this->assertNull(app(UserEmails::class)->emailOf(999999));
    }
}
