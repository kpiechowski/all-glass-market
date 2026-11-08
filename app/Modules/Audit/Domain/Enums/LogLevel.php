<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** The eight PSR-3 levels a log entry carries (FR-16), lowest first. Only levels that need attention carry a colour (D-21). */
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
            self::Debug, self::Info, self::Notice => 'gray',
            self::Warning => 'warning',
            self::Error, self::Critical, self::Alert, self::Emergency => 'danger',
        };
    }
}
