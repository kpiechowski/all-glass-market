<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** §7: Draft, Pending, Rejected, Active and Withdrawn; Reserved and Sold join them with their transitions. Being in the Trash is not a status (D-30). */
enum OfferStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Active = 'active';
    case Withdrawn = 'withdrawn';

    public function getLabel(): string
    {
        return __('offers::enums.offer_status.'.$this->value);
    }

    /** Pending needs the admin's attention, Rejected is a refusal, Active is live; a Draft and a Withdrawn Offer need nothing. */
    public function getColor(): string
    {
        return match ($this) {
            self::Draft, self::Withdrawn => 'gray',
            self::Pending => 'warning',
            self::Rejected => 'danger',
            self::Active => 'success',
        };
    }
}
