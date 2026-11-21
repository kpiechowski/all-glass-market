<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** §7: Draft, Pending, Rejected, Active, Reserved, Sold and Withdrawn. Being in the Trash is not a status (D-30). */
enum OfferStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Active = 'active';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Withdrawn = 'withdrawn';

    public function getLabel(): string
    {
        return __('offers::enums.offer_status.'.$this->value);
    }

    /** Pending needs the admin's attention, Reserved waits for its buyer, Rejected is a refusal, Active is live; a Draft, a Withdrawn and a Sold Offer need nothing. */
    public function getColor(): string
    {
        return match ($this) {
            self::Draft, self::Withdrawn, self::Sold => 'gray',
            self::Pending, self::Reserved => 'warning',
            self::Rejected => 'danger',
            self::Active => 'success',
        };
    }
}
