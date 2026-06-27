<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\AssignUserRole;

use App\Modules\Users\Domain\Enums\RoleEnum;

readonly class AssignUserRoleCommand
{
    public function __construct(
        public int|string $actorId,
        public int|string $userId,
        public RoleEnum $role,
    ) {}
}
