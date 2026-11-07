<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Preferences\Infrastructure\Persistence;

use App\Modules\Preferences\Domain\Repositories\UserPreferenceRepository;
use App\Modules\Preferences\Infrastructure\Persistence\Models\UserPreferenceModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentUserPreferenceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_for_returns_the_users_preference_under_that_key(): void
    {
        $user = UserModel::factory()->create();
        $row = UserPreferenceModel::factory()->create(['user_id' => $user->id, 'key' => 'quick_access', 'value' => ['users.list']]);

        $preference = app(UserPreferenceRepository::class)->findFor($user->id, 'quick_access');

        $this->assertSame($row->id, $preference?->id());
        $this->assertSame(['users.list'], $preference?->value());
    }

    public function test_find_for_skips_other_users_and_other_keys(): void
    {
        $user = UserModel::factory()->create();
        UserPreferenceModel::factory()->create(['key' => 'quick_access']);
        UserPreferenceModel::factory()->create(['user_id' => $user->id, 'key' => 'density', 'value' => 'compact']);

        $this->assertNull(app(UserPreferenceRepository::class)->findFor($user->id, 'quick_access'));
    }
}
