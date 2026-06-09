<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\CreateUser;

readonly class CreateUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {
    }
}
