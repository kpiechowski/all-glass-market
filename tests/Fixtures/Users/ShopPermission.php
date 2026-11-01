<?php

declare(strict_types=1);

namespace Tests\Fixtures\Users;

use App\Core\Contracts\Permission;

enum ShopPermission: string implements Permission
{
    case Sell = 'shop.sell';
    case Refund = 'shop.refund';
}
