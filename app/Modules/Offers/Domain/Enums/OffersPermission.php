<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Enums;

use App\Core\Contracts\Permission;
use Filament\Support\Contracts\HasLabel;

/** §3: a seller holds Create (and so works on own Offers); an admin also holds ManageAll. */
enum OffersPermission: string implements HasLabel, Permission
{
    case Create = 'offers.create';
    case ManageAll = 'offers.manage_all';

    public function getLabel(): string
    {
        return match ($this) {
            self::Create => __('offers::enums.offers_permission.create'),
            self::ManageAll => __('offers::enums.offers_permission.manage_all'),
        };
    }
}
