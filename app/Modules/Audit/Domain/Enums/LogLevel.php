<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** The eight PSR-3 levels a log entry carries (FR-16), lowest first. */
enum LogLevel: string implements HasColor, HasLabel
{
    case Debug = 'debug';
    case Info = 'info';
    case Notice = 'notice';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';
    case Alert = 'alert';
    case Emergency = 'emergency';

    public function getLabel(): string
    {
        return __('audit::enums.log_level.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Debug => 'gray',
            self::Info, self::Notice => 'info',
            self::Warning => 'warning',
            self::Error, self::Critical, self::Alert, self::Emergency => 'danger',
        };
    }
}
