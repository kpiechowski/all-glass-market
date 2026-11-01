<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\DeactivateUser;

use App\Core\Domain\Actor;

final readonly class DeactivateUserCommand
{
    public function __construct(
        public Actor $actor,
        public int $userId,
    ) {}
}
