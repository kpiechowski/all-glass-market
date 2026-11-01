<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Support;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;

/**
 * WF-06: the session side of impersonation. The panel session switches to the
 * target and remembers who the impersonator is; leaving switches back. Whether
 * the switch is allowed is decided by the command handler, not here.
 */
final class ImpersonationSession
{
    private const string KEY = 'users.impersonator_id';

    public static function isActive(): bool
    {
        return session()->has(self::KEY);
    }

    public static function impersonatorId(): ?int
    {
        $id = session(self::KEY);

        return $id === null ? null : (int) $id;
    }

    public static function impersonator(): ?UserModel
    {
        $id = self::impersonatorId();

        return $id === null ? null : UserModel::find($id);
    }

    public static function start(UserModel $impersonator, UserModel $target): void
    {
        session()->put(self::KEY, (int) $impersonator->getKey());

        self::switchTo($target);
    }

    /**
     * Back to the impersonator's own account. When that account is gone or
     * Deactivated, nobody stays signed in and null is returned.
     */
    public static function leave(): ?UserModel
    {
        $impersonator = self::impersonator();
        session()->forget(self::KEY);

        if ($impersonator === null || ! $impersonator->isActive()) {
            Filament::auth()->logout();

            return null;
        }

        self::switchTo($impersonator);

        return $impersonator;
    }

    /** The session middleware compares the stored password hash with the User's, so it must follow the switch. */
    private static function switchTo(UserModel $user): void
    {
        /** @var SessionGuard $guard */
        $guard = Filament::auth();
        $guard->login($user);

        session()->put('password_hash_'.Auth::getDefaultDriver(), $guard->hashPasswordForCookie($user->getAuthPassword()));
        session()->regenerate();
    }
}
