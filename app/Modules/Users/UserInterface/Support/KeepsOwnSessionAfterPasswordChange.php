<?php

declare(strict_types=1);

namespace App\Modules\Users\UserInterface\Support;

use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;

/**
 * FR-10: a changed password ends every other session of the User, because the
 * session middleware compares the stored password hash with the User's current
 * one. The session that made the change must adopt the new hash to survive.
 */
final class KeepsOwnSessionAfterPasswordChange
{
    public static function for(int $changedUserId): void
    {
        $user = Auth::user();

        if ($user === null || $user->getAuthIdentifier() !== $changedUserId) {
            return;
        }

        $user->refresh();

        /** @var SessionGuard $guard */
        $guard = Auth::guard();

        session()->put(
            'password_hash_'.Auth::getDefaultDriver(),
            $guard->hashPasswordForCookie($user->getAuthPassword()),
        );
    }
}
