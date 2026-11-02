<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\AssignUserRole;

use App\Core\Domain\Actor;

final readonly class AssignUserRoleCommand
{
    public function __construct(
        public Actor $actor,
        public int $userId,
        public string $role,
    ) {}
}
