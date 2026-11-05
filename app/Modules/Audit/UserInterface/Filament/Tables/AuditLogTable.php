<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Tables;

use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Audit\UserInterface\Filament\Actions\RestoreChangeAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

/**
 * Every entry of every module, newest first (FR-12). Reuses the record history's
 * columns and details modal, adds the record type and a link to the record.
 * No export, no create, edit or delete.
 */
class AuditLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ...RecordHistoryTable::columns(),
                ...self::recordColumns(),
            ])
            ->filters(AuditLogTableFilters::make(), layout: FiltersLayout::AboveContentCollapsible)
            ->defaultSort('occurred_at', 'desc')
            ->recordActions([RecordHistoryTable::detailsAction(), RestoreChangeAction::make()])
            ->recordAction('details')
            ->headerActions([])
            ->toolbarActions([]);
    }

    /** @return list<TextColumn> */
    private static function recordColumns(): array
    {
        return [
            TextColumn::make('record_type')
                ->label(__('audit::resources.audit_log.fields.record_type'))
                ->state(fn (ChangeModel $record): string => app(AuditableRecords::class)->findByKey($record->record_type)?->typeName() ?? $record->record_type),
            TextColumn::make('record')
                ->label(__('audit::resources.audit_log.fields.record'))
                ->state(fn (ChangeModel $record): string => self::recordTitle($record))
                ->url(fn (ChangeModel $record): ?string => self::recordUrl($record)),
        ];
    }

    /** The live record when it still exists (soft-deleted or gone: none). */
    private static function liveRecord(ChangeModel $entry): ?object
    {
        $type = app(AuditableRecords::class)->findByKey($entry->record_type);

        return $type === null ? null : $type->modelClass::query()->find($entry->record_id);
    }

    public static function recordTitle(ChangeModel $entry): string
    {
        $record = self::liveRecord($entry);

        if ($record !== null) {
            return app(AuditableRecords::class)->findByKey($entry->record_type)->titleOf($record);
        }

        return $entry->title ?? '#'.$entry->record_id;
    }

    public static function recordUrl(ChangeModel $entry): ?string
    {
        $record = self::liveRecord($entry);

        return $record === null ? null : app(AuditableRecords::class)->findByKey($entry->record_type)->urlOf($record);
    }
}
