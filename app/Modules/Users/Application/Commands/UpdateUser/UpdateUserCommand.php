<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\UpdateUser;

readonly class UpdateUserCommand
{
    public function __construct(
        public int|string $id,
        public string $name,
        public string $email,
        public ?string $password = null,
    ) {
    }
}
