<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/** How a Journal entry looks (D-16); it drives nothing else. */
enum JournalSeverity: string implements HasColor, HasIcon, HasLabel
{
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Rejection = 'rejection';

    public function getLabel(): string
    {
        return __('users::enums.journal_severity.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Info => 'primary',
            self::Success => 'success',
            self::Warning => 'warning',
            self::Rejection => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Info => 'heroicon-o-information-circle',
            self::Success => 'heroicon-o-check-circle',
            self::Warning => 'heroicon-o-exclamation-triangle',
            self::Rejection => 'heroicon-o-x-circle',
        };
    }
}
