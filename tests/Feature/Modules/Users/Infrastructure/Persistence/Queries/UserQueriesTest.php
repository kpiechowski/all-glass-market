<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Persistence\Queries;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\Infrastructure\Persistence\Queries\UserQueries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_returns_every_user_newest_first(): void
    {
        $old = UserModel::factory()->create(['created_at' => now()->subDay()]);
        $new = UserModel::factory()->create(['created_at' => now()]);

        $this->assertSame([$new->id, $old->id], app(UserQueries::class)->listing()->pluck('id')->all());
    }

    public function test_only_active_keeps_active_users_and_excludes_deactivated_ones(): void
    {
        $active = UserModel::factory()->create();
        UserModel::factory()->deactivated()->create();

        $queries = app(UserQueries::class);

        $this->assertSame([$active->id], $queries->onlyActive($queries->listing())->pluck('id')->all());
    }

    public function test_only_inactive_keeps_deactivated_users_and_excludes_active_ones(): void
    {
        UserModel::factory()->create();
        $inactive = UserModel::factory()->deactivated()->create();

        $queries = app(UserQueries::class);

        $this->assertSame([$inactive->id], $queries->onlyInactive($queries->listing())->pluck('id')->all());
    }
}
