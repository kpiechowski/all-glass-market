<?php

declare(strict_types=1);

namespace App\Core\UserInterface;

use App\Core\Contracts\Authorizer;
use App\Core\Contracts\Permission;
use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;

/**
 * The logged-in user as an Actor. The UI builds no other actor.
 */
final class CurrentActor
{
    public static function get(): Actor
    {
        return Actor::user(auth()->id() ?? throw new AuthorizationException);
    }

    public static function can(Permission $permission): bool
    {
        return auth()->check() && app(Authorizer::class)->can(self::get(), $permission);
    }
}
