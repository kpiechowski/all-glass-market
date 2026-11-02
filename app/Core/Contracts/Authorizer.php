<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;

/**
 * Implemented by the Users module. A system actor always passes.
 */
interface Authorizer
{
    public function can(Actor $actor, Permission $permission): bool;

    /**
     * @throws AuthorizationException
     */
    public function ensure(Actor $actor, Permission $permission): void;
}
