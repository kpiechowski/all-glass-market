<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\ChangeUserPassword;

use App\Core\Domain\Actor;

final readonly class ChangeUserPasswordCommand
{
    public function __construct(
        public Actor $actor,
        public int $userId,
        public string $password,
    ) {}
}
