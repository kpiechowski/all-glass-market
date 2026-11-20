<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** §7: Draft, Pending, Rejected and Active; the other statuses join them with their transitions. */
enum OfferStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Active = 'active';

    public function getLabel(): string
    {
        return __('offers::enums.offer_status.'.$this->value);
    }

    /** Pending needs the admin's attention, Rejected is a refusal, Active is live; a Draft needs nothing. */
    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Rejected => 'danger',
            self::Active => 'success',
        };
    }
}
