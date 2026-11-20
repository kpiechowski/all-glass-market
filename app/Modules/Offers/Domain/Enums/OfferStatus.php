<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** §7: only Draft exists until sending for approval is built; the other statuses join it with their transitions. */
enum OfferStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';

    public function getLabel(): string
    {
        return __('offers::enums.offer_status.'.$this->value);
    }

    /** A Draft needs no attention. */
    public function getColor(): string
    {
        return 'gray';
    }
}
