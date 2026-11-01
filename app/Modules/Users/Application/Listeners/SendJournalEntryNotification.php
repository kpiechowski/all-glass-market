<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Listeners;

use App\Core\Contracts\PanelNotifier;
use App\Modules\Users\Application\ModuleApi\JournalEntryUrls;
use App\Modules\Users\Application\ModuleApi\JournalSubjects;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Domain\Events\JournalEntryAdded;
use App\Modules\Users\Domain\Repositories\JournalEntryRepository;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Str;
use Throwable;

/**
 * FR-32: every new entry also reaches the owner's panel bell. The notification
 * opens that entry and marks it read; the "go to subject" button is named after
 * the entry's kind and present only when the subject can be reached. A failure
 * here is reported and never undoes the entry or fails the writing module.
 */
final class SendJournalEntryNotification implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private JournalEntryRepository $entries,
        private JournalEntryUrls $urls,
        private JournalSubjects $subjects,
        private PanelNotifier $notifier,
    ) {}

    public function handle(JournalEntryAdded $event): void
    {
        try {
            $this->send($event);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function send(JournalEntryAdded $event): void
    {
        $entry = $this->entries->get($event->entryId);

        $actions = [[
            'label' => __('users::notifications.journal_entry.open'),
            'url' => $this->urls->show($event->entryId),
            'markAsRead' => true,
        ]];

        $subjectUrl = $entry->subjectId() === null ? null : $this->subjects->urlFor($entry->kind(), $entry->subjectId());

        if ($subjectUrl !== null) {
            $actions[] = ['label' => $this->subjects->goToSubjectLabel($entry->kind()), 'url' => $subjectUrl];
        }

        $this->notifier->notify(
            [$event->ownerId],
            $entry->title(),
            Str::limit($entry->content(), 200),
            $entry->severity() === JournalSeverity::Rejection ? 'danger' : $entry->severity()->value,
            $actions,
        );
    }
}
