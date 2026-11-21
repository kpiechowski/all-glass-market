<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\ModuleApi;

use App\Modules\Users\Application\ModuleApi\AccountTypes;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_answers_for_one_user(): void
    {
        $company = UserModel::factory()->create(['account_type' => 'company']);
        $private = UserModel::factory()->create(['account_type' => 'private']);

        $this->assertSame('company', app(AccountTypes::class)->of($company->id));
        $this->assertSame('private', app(AccountTypes::class)->of($private->id));
        $this->assertNull(app(AccountTypes::class)->of(999999));
    }

    public function test_it_answers_for_many_users_in_one_read_and_leaves_out_the_rest(): void
    {
        $company = UserModel::factory()->create(['account_type' => 'company']);
        $private = UserModel::factory()->create(['account_type' => 'private']);
        UserModel::factory()->create(['account_type' => 'company']);

        $types = app(AccountTypes::class)->ofMany([$company->id, $private->id, 999999, $company->id]);

        $this->assertSame([$company->id => 'company', $private->id => 'private'], $types);
    }
}
