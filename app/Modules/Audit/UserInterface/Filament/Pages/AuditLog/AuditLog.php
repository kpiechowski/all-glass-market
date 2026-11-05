<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Pages\AuditLog;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Audit\Domain\Enums\AuditPermission;
use App\Modules\Audit\Infrastructure\Persistence\Queries\ChangeQueries;
use App\Modules\Audit\UserInterface\Filament\Tables\AuditLogTable;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/** The "Dziennik audytu" page (FR-12): holders of "dziennik audytu" only. */
class AuditLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $slug = 'audit-log';

    protected static ?int $navigationSort = 90;

    protected string $view = 'audit::audit-log';

    public static function canAccess(): bool
    {
        return CurrentActor::can(AuditPermission::AuditLog);
    }

    public static function getNavigationLabel(): string
    {
        return __('audit::resources.audit_log.navigation_label');
    }

    public function getTitle(): string
    {
        return __('audit::resources.audit_log.title');
    }

    public function table(Table $table): Table
    {
        return AuditLogTable::configure($table)
            ->query(fn () => app(ChangeQueries::class)->all());
    }
}
