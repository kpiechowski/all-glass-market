<?php

declare(strict_types=1);

namespace App\Modules\Audit\Application\Services;

use App\Modules\Users\Application\ModuleApi\UserEmails;
use Illuminate\Support\Facades\Log;

/**
 * FR-17: writes one entry to the bezpieczeństwo channel. The context is built
 * from named fields only (who, IP address, whom), so a password, token or
 * remember token cannot reach it. These are not Change entries (RULE-01);
 * the log line's own time is the "when".
 */
final class SecurityLog
{
    public const string CHANNEL = 'bezpieczenstwo';

    public function __construct(private UserEmails $emails) {}

    public function forUser(string $event, ?int $userId): void
    {
        $this->write($event, $this->who($userId));
    }

    public function forEmail(string $event, string $email): void
    {
        $this->write($event, ['user_id' => null, 'email' => $email]);
    }

    public function forImpersonation(string $event, int $impersonatorId, int $targetUserId): void
    {
        $target = $this->who($targetUserId);

        $this->write($event, $this->who($impersonatorId) + [
            'target_user_id' => $target['user_id'],
            'target_email' => $target['email'],
        ]);
    }

    /**
     * @return array{user_id: int|null, email: string|null}
     */
    private function who(?int $userId): array
    {
        return ['user_id' => $userId, 'email' => $userId === null ? null : $this->emails->emailOf($userId)];
    }

    /**
     * @param  array<string, int|string|null>  $who
     */
    private function write(string $event, array $who): void
    {
        Log::channel(self::CHANNEL)->info(__('audit::security.'.$event), ['event' => $event] + $who + ['ip' => request()->ip()]);
    }
}
