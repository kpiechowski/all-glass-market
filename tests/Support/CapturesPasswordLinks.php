<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Infrastructure\Notifications\PasswordLinkNotification;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Support\Facades\Notification;

/** Fakes notifications, turns e-mail sending "on" and reads the sent link back. */
trait CapturesPasswordLinks
{
    protected function emailSendingWorks(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
    }

    /** @return array{url: string, email: string, token: string} */
    protected function sentLink(UserModel $user, PasswordLinkKind $kind): array
    {
        $url = null;

        Notification::assertSentTo($user, PasswordLinkNotification::class, function (PasswordLinkNotification $n) use ($kind, &$url): bool {
            if ($n->kind !== $kind) {
                return false;
            }

            $url = $n->url;

            return true;
        });

        parse_str((string) parse_url((string) $url, PHP_URL_QUERY), $query);

        return ['url' => (string) $url, 'email' => (string) $query['email'], 'token' => (string) $query['token']];
    }
}
