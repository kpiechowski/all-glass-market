<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Enums\PermissionEnum;
use App\Modules\Users\Domain\Enums\RoleEnum;

return [
    'permissions' => [
        RoleEnum::Root->value => '*',

        RoleEnum::Admin->value => [
            PermissionEnum::ManageUsers->value,
        ],

        RoleEnum::Client->value => [],
    ],
];
