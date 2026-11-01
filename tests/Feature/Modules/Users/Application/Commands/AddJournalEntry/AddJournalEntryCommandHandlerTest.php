<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\AddJournalEntry;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\Commands\AddJournalEntry\AddJournalEntryCommand;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Domain\Events\JournalEntryAdded;
use App\Modules\Users\Domain\Exceptions\JournalRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AddJournalEntryCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function add(int $userId, string $kind = 'offer', string $content = 'Treść', ?int $subjectId = 5): mixed
    {
        return app(CommandBus::class)->send(new AddJournalEntryCommand(
            Actor::system('test'), $userId, $kind, JournalSeverity::Success->value, 'Tytuł', $content, $subjectId,
        ));
    }

    public function test_it_adds_an_unread_entry_for_the_user_and_announces_it(): void
    {
        Event::fake([JournalEntryAdded::class]);
        $user = UserModel::factory()->create();

        $id = $this->add($user->id);

        $entry = JournalEntryModel::findOrFail($id);
        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame('offer', $entry->kind);
        $this->assertSame(JournalSeverity::Success, $entry->severity);
        $this->assertSame('Tytuł', $entry->title);
        $this->assertSame(5, $entry->subject_id);
        $this->assertFalse($entry->is_read);
        Event::assertDispatched(JournalEntryAdded::class, fn (JournalEntryAdded $e): bool => $e->entryId === $id && $e->ownerId === $user->id);
    }

    public function test_it_stores_markup_as_typed_with_its_line_breaks(): void
    {
        $user = UserModel::factory()->create();

        $id = $this->add($user->id, content: "<b>Powód</b>\n<script>x</script>");

        $this->assertSame("<b>Powód</b>\n<script>x</script>", JournalEntryModel::findOrFail($id)->content);
    }

    public function test_an_unknown_user_gets_no_entry_and_no_error(): void
    {
        Event::fake([JournalEntryAdded::class]);

        $this->assertNull($this->add(9999));

        $this->assertSame(0, JournalEntryModel::count());
        Event::assertNotDispatched(JournalEntryAdded::class);
    }

    public function test_a_kind_that_is_not_a_lowercase_word_is_refused(): void
    {
        $user = UserModel::factory()->create();

        $this->expectException(JournalRefused::class);

        $this->add($user->id, kind: 'Offer!');
    }
}
