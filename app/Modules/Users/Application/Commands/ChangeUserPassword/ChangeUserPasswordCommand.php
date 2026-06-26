<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\ChangeUserPassword;

readonly class ChangeUserPasswordCommand
{
    public function __construct(
        public int|string $id,
        public string $password,
    ) {}
}
