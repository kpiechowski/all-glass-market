<?php

declare(strict_types=1);

namespace Tests\Fixtures\Users;

use App\Modules\Users\Domain\Contracts\Role;

enum ShopRole: string implements Role
{
    case Cashier = 'shop_cashier';

    public function label(): string
    {
        return 'Cashier';
    }
}
