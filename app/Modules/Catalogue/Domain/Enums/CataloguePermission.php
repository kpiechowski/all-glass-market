<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Enums;

use App\Core\Contracts\Permission;
use Filament\Support\Contracts\HasLabel;

/** D-42: Admin and Super Admin only; a Seller holds none. */
enum CataloguePermission: string implements HasLabel, Permission
{
    case Manage = 'catalogue.manage';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manage => __('catalogue::enums.catalogue_permission.manage'),
        };
    }
}
