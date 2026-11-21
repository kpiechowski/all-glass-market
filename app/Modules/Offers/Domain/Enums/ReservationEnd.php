<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

/** §8: how a Reservation ended. */
enum ReservationEnd: string implements HasLabel
{
    case Released = 'released';
    case Sold = 'sold';

    public function getLabel(): string
    {
        return __('offers::enums.reservation_end.'.$this->value);
    }
}
