<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\ModuleApi;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\ModuleApi\JournalWriter;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JournalWriterTest extends TestCase
{
    use RefreshDatabase;

    public function test_another_module_adds_an_entry_for_a_user(): void
    {
        $user = UserModel::factory()->create();

        $id = app(JournalWriter::class)->add(
            Actor::system('offers'), $user->id, 'offer', JournalSeverity::Info, 'Utworzono', 'Treść', 12,
        );

        $entry = JournalEntryModel::findOrFail($id);
        $this->assertSame(12, $entry->subject_id);
        $this->assertSame(JournalSeverity::Info, $entry->severity);
    }

    public function test_an_unknown_user_never_fails_the_callers_operation(): void
    {
        $result = DB::transaction(function () {
            $id = app(JournalWriter::class)->add(Actor::system('offers'), 9999, 'offer', JournalSeverity::Info, 'T', 'C');

            DB::table('cache')->insert(['key' => 'caller-work', 'value' => 'done', 'expiration' => 1]);

            return $id;
        });

        $this->assertNull($result);
        $this->assertSame(1, DB::table('cache')->where('key', 'caller-work')->count());
        $this->assertSame(0, JournalEntryModel::count());
    }
}
