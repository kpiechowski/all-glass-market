<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Pages;

use App\Modules\Users\Application\ModuleApi\JournalSubjects;
use App\Modules\Users\Domain\Contracts\JournalSubjectResolver;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\Pages\ViewJournalEntry;
use Filament\Facades\Filament;
use Filament\Notifications\NotificationMailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewJournalEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_opening_the_details_marks_the_entry_read_and_shows_title_and_content(): void
    {
        $me = UserModel::factory()->create();
        $entry = JournalEntryModel::factory()->create(['user_id' => $me->id, 'title' => 'Oferta aktywna', 'content' => 'Zatwierdzona']);
        $this->actingAs($me);

        Livewire::test(ViewJournalEntry::class, ['record' => $entry->getKey()])
            ->assertSee(['Oferta aktywna', 'Zatwierdzona']);

        $this->assertTrue($entry->fresh()->is_read);
    }

    public function test_content_that_looks_like_html_is_shown_as_typed(): void
    {
        $me = UserModel::factory()->create();
        $entry = JournalEntryModel::factory()->create(['user_id' => $me->id, 'content' => '<script>alert(1)</script><b>pogrubione</b>']);
        $this->actingAs($me);

        Livewire::test(ViewJournalEntry::class, ['record' => $entry->getKey()])
            ->assertSeeHtml('&lt;script&gt;alert(1)&lt;/script&gt;&lt;b&gt;pogrubione&lt;/b&gt;')
            ->assertDontSeeHtml('<script>alert(1)</script>')
            ->assertDontSeeHtml('<b>pogrubione</b>');
    }

    public function test_another_users_entry_cannot_be_opened_and_stays_unread(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $entry = JournalEntryModel::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ViewJournalEntry::class, ['record' => $entry->getKey()])->assertNotFound();

        $this->assertFalse($entry->fresh()->is_read);
    }

    public function test_the_subject_button_is_named_after_the_kind_and_follows_the_link(): void
    {
        app(JournalSubjects::class)->register('offer', new class implements JournalSubjectResolver
        {
            public function urlFor(int $subjectId): ?string
            {
                return $subjectId === 12 ? '/oferty/12' : null;
            }
        });
        $me = UserModel::factory()->create();
        $entry = JournalEntryModel::factory()->forSubject(12)->create(['user_id' => $me->id]);
        $this->actingAs($me);

        Livewire::test(ViewJournalEntry::class, ['record' => $entry->getKey()])
            ->assertSee('Przejdź do oferty')
            ->callAction('go_to_subject')
            ->assertRedirect('/oferty/12');
    }

    public function test_a_subject_that_no_longer_exists_shows_a_warning_and_the_entry_stays(): void
    {
        $me = UserModel::factory()->create();
        $entry = JournalEntryModel::factory()->forSubject(404)->create(['user_id' => $me->id]);
        $this->actingAs($me);
        $mailbox = $this->spy(NotificationMailbox::class);

        Livewire::test(ViewJournalEntry::class, ['record' => $entry->getKey()])
            ->callAction('go_to_subject')
            ->assertNoRedirect();

        $mailbox->shouldHaveReceived('push')->withArgs(fn (array $n): bool => $n['status'] === 'warning');
        $this->assertNotNull($entry->fresh());
    }

    public function test_an_entry_without_a_link_has_no_subject_button(): void
    {
        $me = UserModel::factory()->create();
        $entry = JournalEntryModel::factory()->create(['user_id' => $me->id]);
        $this->actingAs($me);

        Livewire::test(ViewJournalEntry::class, ['record' => $entry->getKey()])->assertActionHidden('go_to_subject');
    }
}
