<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\ReactivateUser;

use App\Core\Domain\Actor;

final readonly class ReactivateUserCommand
{
    public function __construct(
        public Actor $actor,
        public int $userId,
    ) {}
}
