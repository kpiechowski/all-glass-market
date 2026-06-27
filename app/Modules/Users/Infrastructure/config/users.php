<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Enums\PermissionEnum;
use App\Modules\Users\Domain\Enums\RoleEnum;

return [
    'permissions' => [
        RoleEnum::Root->value => '*',

        RoleEnum::Admin->value => [
            PermissionEnum::ViewAnyUser->value,
            PermissionEnum::CreateUser->value,
            PermissionEnum::UpdateAnyUser->value,
            PermissionEnum::DeleteAnyUser->value,
            PermissionEnum::ChangeAnyUserPassword->value,
            PermissionEnum::AssignUserRole->value,
        ],

        RoleEnum::Client->value => [],
    ],
];
