<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\GrantSuperAdmin;

use App\Core\Domain\Actor;

final readonly class GrantSuperAdminCommand
{
    public function __construct(
        public Actor $actor,
        public string $email,
    ) {}
}
