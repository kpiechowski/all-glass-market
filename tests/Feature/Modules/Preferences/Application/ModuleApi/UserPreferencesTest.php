<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Preferences\Application\ModuleApi;

use App\Core\Domain\Actor;
use App\Modules\Preferences\Application\ModuleApi\QuickAccess;
use App\Modules\Preferences\Application\ModuleApi\UserPreferences;
use App\Modules\Preferences\Domain\Exceptions\PreferenceRefused;
use App\Modules\Preferences\Infrastructure\Persistence\Models\UserPreferenceModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_without_a_stored_value_gets_the_definitions_default(): void
    {
        $user = UserModel::factory()->create();

        $this->assertSame([], app(UserPreferences::class)->get($user->id, 'quick_access'));
    }

    public function test_save_stores_through_the_command_and_get_reads_it_back(): void
    {
        $user = UserModel::factory()->create();
        $preferences = app(UserPreferences::class);

        $preferences->save(Actor::user($user->id), 'quick_access', ['users.profile', 'users.list']);

        $this->assertSame(['users.profile', 'users.list'], $preferences->get($user->id, 'quick_access'));
        $this->assertSame(1, UserPreferenceModel::query()->count());
    }

    public function test_get_refuses_an_unknown_preference(): void
    {
        $this->expectException(PreferenceRefused::class);

        app(UserPreferences::class)->get(1, 'favourite_colour');
    }

    public function test_quick_access_returns_the_stored_keys_as_a_list(): void
    {
        $user = UserModel::factory()->create();
        UserPreferenceModel::factory()->create(['user_id' => $user->id, 'key' => 'quick_access', 'value' => ['audit.log', 'users.list']]);

        $this->assertSame(['audit.log', 'users.list'], app(QuickAccess::class)->keysFor($user->id));
        $this->assertSame([], app(QuickAccess::class)->keysFor(UserModel::factory()->create()->id));
    }
}
