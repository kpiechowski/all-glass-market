<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Listeners;

use App\Core\Contracts\PanelNotifier;
use App\Modules\Users\Application\ModuleApi\JournalSubjects;
use App\Modules\Users\Domain\Contracts\JournalSubjectResolver;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Domain\Events\JournalEntryAdded;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class SendJournalEntryNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function registerOfferSubjects(): void
    {
        app(JournalSubjects::class)->register('offer', new class implements JournalSubjectResolver
        {
            public function urlFor(int $subjectId): ?string
            {
                return $subjectId === 12 ? '/oferty/12' : null;
            }
        });
    }

    public function test_the_owner_gets_a_notification_that_opens_the_entry_and_marks_it_read(): void
    {
        $this->registerOfferSubjects();
        $entry = JournalEntryModel::factory()->forSubject(12)->create(['severity' => JournalSeverity::Rejection, 'title' => 'Odrzucona']);

        $this->mock(PanelNotifier::class, function (MockInterface $notifier) use ($entry): void {
            $notifier->shouldReceive('notify')->once()->withArgs(function (array $ids, string $title, string $body, string $status, array $actions) use ($entry): bool {
                return $ids === [$entry->user_id]
                    && $title === 'Odrzucona'
                    && $status === 'danger'
                    && $actions[0]['markAsRead'] === true
                    && str_ends_with($actions[0]['url'], '/journal/'.$entry->id)
                    && $actions[1] === ['label' => 'Przejdź do oferty', 'url' => '/oferty/12'];
            });
        });

        event(new JournalEntryAdded($entry->id, $entry->user_id));
    }

    public function test_there_is_no_subject_button_without_a_link_or_when_the_subject_is_gone(): void
    {
        $this->registerOfferSubjects();
        $withoutLink = JournalEntryModel::factory()->create();
        $gone = JournalEntryModel::factory()->forSubject(99)->create();

        $this->mock(PanelNotifier::class, function (MockInterface $notifier): void {
            $notifier->shouldReceive('notify')->twice()->withArgs(fn (array $ids, string $title, string $body, string $status, array $actions): bool => count($actions) === 1);
        });

        event(new JournalEntryAdded($withoutLink->id, $withoutLink->user_id));
        event(new JournalEntryAdded($gone->id, $gone->user_id));
    }

    public function test_a_failing_notification_does_not_fail_the_writer(): void
    {
        $entry = JournalEntryModel::factory()->create();
        $this->mock(PanelNotifier::class)->shouldReceive('notify')->andThrow(new RuntimeException('bell down'));

        event(new JournalEntryAdded($entry->id, $entry->user_id));

        $this->assertSame(1, JournalEntryModel::count());
    }
}
