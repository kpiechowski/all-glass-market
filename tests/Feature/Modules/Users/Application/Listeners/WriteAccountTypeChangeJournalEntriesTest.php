<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Listeners;

use App\Modules\Users\Application\ModuleApi\JournalSubjects;
use App\Modules\Users\Domain\Events\AccountTypeChanged;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-21, WF-02: a `users` Journal entry for each member of "Admins". */
class WriteAccountTypeChangeJournalEntriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_gets_an_entry_linking_to_the_user(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $superAdmin = UserModel::factory()->superAdmin()->create();
        $plain = UserModel::factory()->create();
        $user = UserModel::factory()->create(['name' => 'Jan Kowalski']);

        event(new AccountTypeChanged($user->id, 'company', 'private', CarbonImmutable::parse('2026-11-01 10:05:00')));

        $entries = JournalEntryModel::query()->get();
        $this->assertEqualsCanonicalizing([$admin->id, $superAdmin->id], $entries->pluck('user_id')->all());
        $this->assertSame(0, JournalEntryModel::query()->where('user_id', $plain->id)->count());
        $entry = $entries->first();
        $this->assertSame('users', $entry->kind);
        $this->assertSame('Użytkownik Jan Kowalski zmienił rodzaj konta na: Firmowe', $entry->title);
        $this->assertSame($user->id, $entry->subject_id);
        $this->assertSame('Jan Kowalski zmienił rodzaj konta z: Prywatne na: Firmowe (01.11.2026 11:05)', $entry->content);
    }

    public function test_private_reads_prywatne(): void
    {
        UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create(['name' => 'Anna']);

        event(new AccountTypeChanged($user->id, 'private', 'company', CarbonImmutable::parse('2026-11-01 10:05:00')));

        $this->assertStringEndsWith('na: Prywatne', JournalEntryModel::query()->first()->title);
    }

    public function test_the_users_subject_resolves_to_the_user_page(): void
    {
        $user = UserModel::factory()->create();
        $subjects = app(JournalSubjects::class);

        $this->assertStringEndsWith('/users/'.$user->id, $subjects->urlFor('users', $user->id));
        $this->assertNull($subjects->urlFor('users', 999999));
    }
}
