<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

/** Stan: Nowa, Używana or Outlet. A category, so no badge colour. */
enum OfferCondition: string implements HasLabel
{
    case New = 'new';
    case Used = 'used';
    case Outlet = 'outlet';

    public function getLabel(): string
    {
        return __('offers::enums.offer_condition.'.$this->value);
    }
}
