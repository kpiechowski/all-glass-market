<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Enums;

use App\Core\Contracts\Permission;

enum UsersPermission: string implements Permission
{
    case ManageUsers = 'users.manage';
    case Impersonate = 'users.impersonate';
}
