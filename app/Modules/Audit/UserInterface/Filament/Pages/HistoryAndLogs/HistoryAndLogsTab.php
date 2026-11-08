<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Pages\HistoryAndLogs;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Audit\Domain\Enums\AuditPermission;

/** The page tabs of "Historia i logi" (D-24a). The value is the Polish slug in `?zakladka=`. */
enum HistoryAndLogsTab: string
{
    case History = 'historia';
    case TechnicalLogs = 'logi';
    case FailedJobs = 'zadania';

    public function label(): string
    {
        return __('audit::pages.history_and_logs.tabs.'.$this->value);
    }

    public function permission(): AuditPermission
    {
        return match ($this) {
            self::History => AuditPermission::AuditLog,
            self::TechnicalLogs, self::FailedJobs => AuditPermission::TechnicalLogs,
        };
    }

    public function isAllowed(): bool
    {
        return CurrentActor::can($this->permission());
    }

    /** @return list<self> the tabs the current user may see, in bar order */
    public static function allowed(): array
    {
        return array_values(array_filter(self::cases(), fn (self $tab): bool => $tab->isAllowed()));
    }
}
