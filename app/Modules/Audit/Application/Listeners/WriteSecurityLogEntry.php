<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Listeners;

use App\Modules\Audit\Application\Services\SecurityLog;
use App\Modules\Users\Domain\Events\ImpersonationEnded;
use App\Modules\Users\Domain\Events\ImpersonationStarted;
use App\Modules\Users\Domain\Events\PasswordResetCompleted;
use App\Modules\Users\Domain\Events\PasswordResetRequested;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * FR-17: each security fact becomes one entry in the bezpieczeństwo channel.
 * One method per event, one reaction: log. It writes no data and sends no
 * command, so it runs inline and needs no queue.
 */
final class WriteSecurityLogEntry
{
    public function __construct(private SecurityLog $log) {}

    public function signedIn(Login $event): void
    {
        $this->log->forUser('sign_in', $this->idOf($event->user));
    }

    public function signedOut(Logout $event): void
    {
        $this->log->forUser('sign_out', $this->idOf($event->user));
    }

    public function signInFailed(Failed $event): void
    {
        $this->log->forEmail('sign_in_failed', (string) ($event->credentials['email'] ?? ''));
    }

    public function passwordResetRequested(PasswordResetRequested $event): void
    {
        $this->log->forUser('password_reset_requested', $event->userId);
    }

    public function passwordResetCompleted(PasswordResetCompleted $event): void
    {
        $this->log->forUser('password_reset_completed', $event->userId);
    }

    public function impersonationStarted(ImpersonationStarted $event): void
    {
        $this->log->forImpersonation('impersonation_started', $event->impersonatorId, $event->targetUserId);
    }

    public function impersonationEnded(ImpersonationEnded $event): void
    {
        $this->log->forImpersonation('impersonation_ended', $event->impersonatorId, $event->targetUserId);
    }

    private function idOf(?object $user): ?int
    {
        return $user !== null && method_exists($user, 'getAuthIdentifier') ? (int) $user->getAuthIdentifier() : null;
    }
}
