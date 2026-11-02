<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

use App\Core\Contracts\Authorizer;
use App\Core\Contracts\Permission;
use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;

final class ArrayAuthorizer implements Authorizer
{
    /** @param  array<int, Permission>  $granted */
    public function __construct(private array $granted = []) {}

    public function can(Actor $actor, Permission $permission): bool
    {
        return $actor->isSystem() || in_array($permission, $this->granted, true);
    }

    public function ensure(Actor $actor, Permission $permission): void
    {
        if (! $this->can($actor, $permission)) {
            throw new AuthorizationException;
        }
    }
}
