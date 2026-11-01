<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Auth;

use App\Modules\Users\Domain\Contracts\PasswordLinks;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Infrastructure\Notifications\PasswordLinkNotification;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Laravel's password brokers hold the tokens: 'users' for reset links
 * (60 minutes) and 'invitations' for invitation links (7 days), each with its
 * own table and expiry (config/auth.php). A broker deletes a token when it is
 * redeemed, which makes the link single-use.
 */
final class LaravelPasswordLinks implements PasswordLinks
{
    /** Signed routes of the admin panel: Filament's reset page and the invitation route of AdminPanelProvider. */
    private const string RESET_ROUTE = 'filament.admin.auth.password-reset.reset';

    private const string INVITATION_ROUTE = 'filament.admin.auth.invitation';

    public function send(int $userId, PasswordLinkKind $kind): void
    {
        $user = UserModel::query()->find($userId) ?? throw UserRefused::userNotFound();
        $broker = $this->broker($kind);

        // Replaces an earlier token, so a re-sent link voids the old one.
        $token = $broker->createToken($user);

        try {
            $user->notify(new PasswordLinkNotification($kind, $this->urlFor($kind, $user->email, $token)));
        } catch (Throwable $exception) {
            report($exception);
            $broker->deleteToken($user);

            throw UserRefused::passwordLinkNotSent();
        }
    }

    public function redeem(string $email, string $token, PasswordLinkKind $kind): ?int
    {
        $userId = null;

        $status = Password::broker($this->brokerName($kind))->reset(
            ['email' => $email, 'token' => $token, 'password' => 'unused'],
            function (UserModel $user) use (&$userId): void {
                $userId = (int) $user->getKey();
            },
        );

        return $status === Password::PASSWORD_RESET ? $userId : null;
    }

    private function urlFor(PasswordLinkKind $kind, string $email, string $token): string
    {
        return URL::signedRoute(
            $kind === PasswordLinkKind::Invitation ? self::INVITATION_ROUTE : self::RESET_ROUTE,
            ['email' => $email, 'token' => $token],
        );
    }

    private function broker(PasswordLinkKind $kind): PasswordBroker
    {
        /** @var PasswordBroker */
        return Password::broker($this->brokerName($kind));
    }

    private function brokerName(PasswordLinkKind $kind): string
    {
        return $kind === PasswordLinkKind::Invitation ? 'invitations' : 'users';
    }
}
