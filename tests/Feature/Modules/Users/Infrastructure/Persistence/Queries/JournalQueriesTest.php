<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Persistence\Queries;

use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\Infrastructure\Persistence\Queries\JournalQueries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_owned_by_returns_only_the_owners_entries_newest_first(): void
    {
        $owner = UserModel::factory()->create();
        $other = UserModel::factory()->create();
        $older = JournalEntryModel::factory()->create(['user_id' => $owner->id, 'created_at' => now()->subDay()]);
        $newer = JournalEntryModel::factory()->create(['user_id' => $owner->id, 'created_at' => now()]);
        JournalEntryModel::factory()->create(['user_id' => $other->id]);

        $ids = app(JournalQueries::class)->ownedBy($owner->id)->pluck('id')->all();

        $this->assertSame([$newer->id, $older->id], $ids);
    }

    public function test_an_admin_sees_only_their_own_entries(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create();
        JournalEntryModel::factory()->create(['user_id' => $user->id]);

        $this->assertSame(0, app(JournalQueries::class)->ownedBy($admin->id)->count());
    }

    public function test_it_counts_only_the_owners_unread_entries(): void
    {
        $owner = UserModel::factory()->create();
        $other = UserModel::factory()->create();
        JournalEntryModel::factory()->count(2)->create(['user_id' => $owner->id]);
        JournalEntryModel::factory()->read()->create(['user_id' => $owner->id]);
        JournalEntryModel::factory()->create(['user_id' => $other->id]);

        $this->assertSame(2, app(JournalQueries::class)->unreadCountFor($owner->id));
    }

    public function test_it_lists_the_owners_kinds_once_and_leaves_out_other_users_kinds(): void
    {
        $owner = UserModel::factory()->create();
        $other = UserModel::factory()->create();
        JournalEntryModel::factory()->count(2)->create(['user_id' => $owner->id, 'kind' => 'offer']);
        JournalEntryModel::factory()->create(['user_id' => $owner->id, 'kind' => 'users']);
        JournalEntryModel::factory()->create(['user_id' => $other->id, 'kind' => 'secret']);

        $this->assertSame(['offer', 'users'], app(JournalQueries::class)->kindsOf($owner->id));
    }
}
