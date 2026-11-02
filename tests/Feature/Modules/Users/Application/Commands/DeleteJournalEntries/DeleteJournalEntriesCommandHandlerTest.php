<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\DeleteJournalEntries;

use App\Core\Contracts\PanelNotifier;
use App\Core\Domain\Actor;
use App\Modules\Users\Application\Commands\DeleteJournalEntries\DeleteJournalEntriesCommand;
use App\Modules\Users\Application\ModuleApi\JournalEntryUrls;
use App\Modules\Users\Domain\Exceptions\JournalRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteJournalEntriesCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_deletes_one_or_many_of_their_entries(): void
    {
        $owner = UserModel::factory()->create();
        $entries = JournalEntryModel::factory()->count(3)->create(['user_id' => $owner->id]);

        app(CommandBus::class)->send(new DeleteJournalEntriesCommand(Actor::user($owner->id), [$entries[0]->id, $entries[1]->id]));

        $this->assertSame([$entries[2]->id], JournalEntryModel::pluck('id')->all());
    }

    public function test_one_foreign_entry_refuses_the_whole_batch(): void
    {
        $owner = UserModel::factory()->create();
        $mine = JournalEntryModel::factory()->create(['user_id' => $owner->id]);
        $theirs = JournalEntryModel::factory()->create();

        try {
            app(CommandBus::class)->send(new DeleteJournalEntriesCommand(Actor::user($owner->id), [$mine->id, $theirs->id]));
            $this->fail('A foreign delete was accepted');
        } catch (JournalRefused) {
            $this->assertSame(2, JournalEntryModel::count());
        }
    }

    public function test_the_system_cannot_delete_entries(): void
    {
        $entry = JournalEntryModel::factory()->create();

        try {
            app(CommandBus::class)->send(new DeleteJournalEntriesCommand(Actor::system('test'), [$entry->id]));
            $this->fail('A system delete was accepted');
        } catch (JournalRefused) {
            $this->assertSame(1, JournalEntryModel::count());
        }
    }

    public function test_a_missing_entry_is_refused(): void
    {
        $owner = UserModel::factory()->create();

        $this->expectException(JournalRefused::class);

        app(CommandBus::class)->send(new DeleteJournalEntriesCommand(Actor::user($owner->id), [999]));
    }

    public function test_deleting_an_entry_deletes_its_panel_notification_and_keeps_the_others(): void
    {
        $owner = UserModel::factory()->create();
        $entries = JournalEntryModel::factory()->count(2)->create(['user_id' => $owner->id]);
        $urls = app(JournalEntryUrls::class);

        foreach ($entries as $entry) {
            app(PanelNotifier::class)->notify([$owner->id], 'Wpis '.$entry->id, '', 'info', [['label' => 'Zobacz wpis', 'url' => $urls->show($entry->id)]]);
        }

        app(CommandBus::class)->send(new DeleteJournalEntriesCommand(Actor::user($owner->id), [$entries[0]->id]));

        $this->assertSame(['Wpis '.$entries[1]->id], $owner->notifications()->get()->pluck('data.title')->all());
    }
}
