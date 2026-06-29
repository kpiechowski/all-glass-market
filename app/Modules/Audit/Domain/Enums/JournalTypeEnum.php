<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum JournalTypeEnum: string implements HasColor, HasIcon, HasLabel
{
    case Info = 'Info';
    case Success = 'Success';
    case Warning = 'Warning';
    case Rejection = 'Rejection';

    public function getLabel(): string
    {
        return __('audit::resource.journal_types.'.$this->value);
    }

    public function getColor(): string|array|null
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
