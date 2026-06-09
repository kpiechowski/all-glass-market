<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Repositories;

use App\Core\Abstracts\ModelRepository;
use App\Modules\Users\Domain\Models\User;

class UserRepository extends ModelRepository
{
    protected function getModel(): string
    {
        return User::class;
    }
}
