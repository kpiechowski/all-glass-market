<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\ModuleApi;

use App\Modules\Users\Application\ModuleApi\AdminRecipients;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRecipientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_admins_and_super_admins_and_leaves_out_everyone_else(): void
    {
        $admin = UserModel::factory()->create(['role' => 'admin']);
        $superAdmin = UserModel::factory()->create(['role' => 'super_admin']);
        UserModel::factory()->create(['role' => 'user']);
        UserModel::factory()->create(['role' => 'shop_cashier']);

        $ids = app(AdminRecipients::class)->userIds();

        $this->assertEqualsCanonicalizing([$admin->id, $superAdmin->id], $ids);
    }
}
