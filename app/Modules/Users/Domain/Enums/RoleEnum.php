<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Enums;

enum RoleEnum: string
{
    case Root = 'root';
    case Admin = 'admin';
    case Client = 'client';
}
