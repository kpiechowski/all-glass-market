<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Listeners;

use App\Core\Domain\Actor;
use App\Modules\Users\Application\ModuleApi\AdminRecipients;
use App\Modules\Users\Application\ModuleApi\JournalWriter;
use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Domain\Events\AccountTypeChanged;
use App\Modules\Users\Domain\Repositories\UserRepository;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Throwable;

/**
 * FR-21, WF-02: tells every member of "Admins" in their Journal that a User
 * changed Account type. Runs after the change committed, and its failure never
 * undoes the change.
 */
final class WriteAccountTypeChangeJournalEntries implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private UserRepository $users,
        private AdminRecipients $admins,
        private JournalWriter $journal,
    ) {}

    public function handle(AccountTypeChanged $event): void
    {
        try {
            $this->write($event);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function write(AccountTypeChanged $event): void
    {
        $user = $this->users->find($event->userId);

        if ($user === null) {
            return;
        }

        $title = __('users::notifications.account_type_changed.title', [
            'name' => $user->name(),
            'type' => AccountType::from($event->accountType)->getLabel(),
        ]);

        $content = __('users::notifications.account_type_changed.content', [
            'name' => $user->name(),
            'from' => AccountType::from($event->previousAccountType)->getLabel(),
            'to' => AccountType::from($event->accountType)->getLabel(),
            'when' => $event->changedAt->setTimezone('Europe/Warsaw')->format('d.m.Y H:i'),
        ]);

        foreach ($this->admins->userIds() as $adminId) {
            $this->journal->add(
                Actor::system('account type changed'),
                $adminId,
                'users',
                JournalSeverity::Info,
                $title,
                $content,
                $event->userId,
            );
        }
    }
}
