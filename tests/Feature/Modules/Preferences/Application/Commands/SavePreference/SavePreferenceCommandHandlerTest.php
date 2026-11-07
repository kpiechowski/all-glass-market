<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Preferences\Application\Commands\SavePreference;

use App\Core\Domain\Actor;
use App\Modules\Preferences\Application\Commands\SavePreference\SavePreferenceCommand;
use App\Modules\Preferences\Domain\Exceptions\PreferenceRefused;
use App\Modules\Preferences\Infrastructure\Persistence\Models\UserPreferenceModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavePreferenceCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, string $key, mixed $value): void
    {
        app(CommandBus::class)->send(new SavePreferenceCommand($actor, $key, $value));
    }

    public function test_a_user_saves_their_own_quick_access_tiles(): void
    {
        $user = UserModel::factory()->create();

        $this->send(Actor::user($user->id), 'quick_access', ['audit.log', 'users.list']);

        $row = UserPreferenceModel::query()->where('user_id', $user->id)->where('key', 'quick_access')->sole();
        $this->assertSame(['audit.log', 'users.list'], $row->value);
    }

    public function test_saving_again_replaces_the_value_in_the_same_row(): void
    {
        $user = UserModel::factory()->create();
        UserPreferenceModel::factory()->create(['user_id' => $user->id, 'key' => 'quick_access', 'value' => ['users.list']]);

        $this->send(Actor::user($user->id), 'quick_access', ['users.profile']);

        $rows = UserPreferenceModel::query()->where('user_id', $user->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame(['users.profile'], $rows->first()->value);
    }

    public function test_it_leaves_other_users_preferences_alone(): void
    {
        $user = UserModel::factory()->create();
        $other = UserPreferenceModel::factory()->create(['key' => 'quick_access', 'value' => ['users.list']]);

        $this->send(Actor::user($user->id), 'quick_access', []);

        $this->assertSame(['users.list'], $other->fresh()->value);
    }

    public function test_an_unknown_preference_is_refused(): void
    {
        $user = UserModel::factory()->create();

        try {
            $this->send(Actor::user($user->id), 'favourite_colour', 'blue');
            $this->fail('An unknown preference was saved');
        } catch (PreferenceRefused $refusal) {
            $this->assertSame('preferences::exceptions.unknown_preference', $refusal->translationKey);
            $this->assertSame(0, UserPreferenceModel::query()->count());
        }
    }

    public function test_a_value_the_definition_refuses_is_not_stored(): void
    {
        $user = UserModel::factory()->create();

        try {
            $this->send(Actor::user($user->id), 'quick_access', ['users.list', 'https://evil.example']);
            $this->fail('An unregistered item was saved');
        } catch (PreferenceRefused $refusal) {
            $this->assertSame('preferences::exceptions.quick_access.unknown_item', $refusal->translationKey);
            $this->assertSame(0, UserPreferenceModel::query()->count());
        }
    }

    public function test_the_system_has_no_preferences_of_its_own(): void
    {
        $this->expectException(PreferenceRefused::class);

        $this->send(Actor::system('test'), 'quick_access', []);
    }
}
