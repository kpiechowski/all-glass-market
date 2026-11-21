<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Enums;

use App\Core\Contracts\Permission;
use Filament\Support\Contracts\HasLabel;

/** §3: a seller holds Create (and so works on own Offers); an admin also holds ManageAll; ChangeVatRate (D-54) is granted to no Role, so only Super Admin's wildcard holds it. */
enum OffersPermission: string implements HasLabel, Permission
{
    case Create = 'offers.create';
    case ManageAll = 'offers.manage_all';
    case ChangeVatRate = 'offers.change_vat_rate';

    public function getLabel(): string
    {
        return match ($this) {
            self::Create => __('offers::enums.offers_permission.create'),
            self::ManageAll => __('offers::enums.offers_permission.manage_all'),
            self::ChangeVatRate => __('offers::enums.offers_permission.change_vat_rate'),
        };
    }
}
