<?php

declare(strict_types=1);

namespace App\Modules\Users\Application\Commands\DeleteUser;

readonly class DeleteUserCommand
{
    public function __construct(
        public int|string $id,
    ) {}
}
