<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\RevokeSuperAdmin;

use App\Core\Domain\Actor;

final readonly class RevokeSuperAdminCommand
{
    public function __construct(
        public Actor $actor,
        public string $email,
    ) {}
}
