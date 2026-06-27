<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Enums;

enum PermissionEnum: string
{
    case ViewAnyUser = 'users.view_any';
    case CreateUser = 'users.create';
    case UpdateAnyUser = 'users.update_any';
    case DeleteAnyUser = 'users.delete_any';
    case ChangeAnyUserPassword = 'users.change_any_password';
    case AssignUserRole = 'users.assign_role';
}
