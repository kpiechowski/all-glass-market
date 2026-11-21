<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** §7 Inquiry states: Sent, Read, then Accepted or Rejected; Sent or Read may become Outdated. */
enum InquiryState: string implements HasColor, HasLabel
{
    case Sent = 'sent';
    case Read = 'read';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Outdated = 'outdated';

    /** An open Inquiry still waits for the owner (or for the Offer to leave Active). */
    public function isOpen(): bool
    {
        return $this === self::Sent || $this === self::Read;
    }

    public function getLabel(): string
    {
        return __('offers::enums.inquiry_state.'.$this->value);
    }

    /** Needs action: warning; a finished answer is neutral. */
    public function getColor(): string
    {
        return match ($this) {
            self::Sent, self::Read => 'warning',
            self::Accepted, self::Rejected, self::Outdated => 'gray',
        };
    }
}
