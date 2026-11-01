<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\MarkJournalEntryRead;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\Commands\MarkJournalEntryRead\MarkJournalEntryReadCommand;
use App\Modules\Users\Domain\Exceptions\JournalRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkJournalEntryReadCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_marks_an_entry_read(): void
    {
        $owner = UserModel::factory()->create();
        $entry = JournalEntryModel::factory()->create(['user_id' => $owner->id]);

        app(CommandBus::class)->send(new MarkJournalEntryReadCommand(Actor::user($owner->id), $entry->id));

        $this->assertTrue($entry->fresh()->is_read);
    }

    public function test_another_user_cannot_mark_it_not_even_an_admin(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $entry = JournalEntryModel::factory()->create();

        try {
            app(CommandBus::class)->send(new MarkJournalEntryReadCommand(Actor::user($admin->id), $entry->id));
            $this->fail('A foreign mark was accepted');
        } catch (JournalRefused) {
            $this->assertFalse($entry->fresh()->is_read);
        }
    }

    public function test_the_system_cannot_mark_an_entry(): void
    {
        $entry = JournalEntryModel::factory()->create();

        $this->expectException(JournalRefused::class);

        app(CommandBus::class)->send(new MarkJournalEntryReadCommand(Actor::system('test'), $entry->id));
    }

    public function test_a_missing_entry_is_refused(): void
    {
        $owner = UserModel::factory()->create();

        $this->expectException(JournalRefused::class);

        app(CommandBus::class)->send(new MarkJournalEntryReadCommand(Actor::user($owner->id), 999));
    }
}
