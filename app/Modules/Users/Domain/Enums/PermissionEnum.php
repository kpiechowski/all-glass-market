<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Enums;

enum PermissionEnum: string
{
    case ManageUsers = 'users.manage';
}
