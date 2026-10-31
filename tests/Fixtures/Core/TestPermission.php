<?php

declare(strict_types=1);

namespace Tests\Fixtures\Core;

use App\Core\Contracts\Permission;

enum TestPermission: string implements Permission
{
    case Activate = 'test.activate';
    case Delete = 'test.delete';
}
