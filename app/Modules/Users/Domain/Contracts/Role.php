<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Contracts;

use BackedEnum;

/**
 * Implemented by each module's role enum, e.g. ShopRole. The backing value is
 * the key stored on the User.
 */
interface Role extends BackedEnum
{
    public function label(): string;
}
