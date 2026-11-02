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
}
