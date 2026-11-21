<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

/** D-34: where a Sale happened. */
enum SaleChannel: string implements HasLabel
{
    case Panel = 'panel';
    case WooCommerce = 'woocommerce';
    case Olx = 'olx';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('offers::enums.sale_channel.'.$this->value);
    }
}
