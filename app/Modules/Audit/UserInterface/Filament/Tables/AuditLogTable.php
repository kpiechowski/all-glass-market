<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Tables;

use App\Core\UserInterface\Filament\Tables\Columns\RecordCardColumn;
use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The "Historia zmian" tab of "Historia i logi" (template A, FR-12): every Change of every
 * module, newest first. The record history's columns and details modal, plus the record (a link
 * while it exists) with its type under it. Five filters, so the funnel. No export, no create,
 * edit or delete.
 */
class AuditLogTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns(self::columns())
            ->filters(AuditLogTableFilters::make())
            ->defaultSort('occurred_at', 'desc')
            ->recordAction('details')
            ->recordActions([RecordHistoryTable::restoreAction()])
            ->headerActions([])
            ->toolbarActions([]);

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedClock,
            heading: __('audit::pages.history_and_logs.history.empty.heading'),
            description: __('audit::pages.history_and_logs.history.empty.description'),
        );
    }

    /** @return list<Column> */
    private static function columns(): array
    {
        return [
            RecordCardColumn::make()
                ->title(fn (ChangeModel $record): string => self::recordTitle($record))
                ->subtitle(fn (ChangeModel $record): string => self::typeName($record))
                ->badges(fn (ChangeModel $record): array => [RecordHistoryTable::kindLabel($record)])
                ->meta(fn (ChangeModel $record): array => [$record->occurred_at->format('d.m.Y H:i'), RecordHistoryTable::actorName($record)]),
            RecordHistoryTable::occurredAtColumn(),
            TextColumn::make('record')
                ->label(__('audit::pages.history_and_logs.history.fields.record'))
                ->state(fn (ChangeModel $record): string => self::recordTitle($record))
                ->description(fn (ChangeModel $record): string => self::typeName($record))
                ->url(fn (ChangeModel $record): ?string => self::recordUrl($record))
                ->visibleFrom('md'),
            RecordHistoryTable::kindColumn(),
            RecordHistoryTable::actorColumn(),
        ];
    }

    private static function typeName(ChangeModel $entry): string
    {
        return app(AuditableRecords::class)->findByKey($entry->record_type)?->typeName() ?? $entry->record_type;
    }

    /** The live record when it still exists (soft-deleted or gone: none); read once per entry for card, cell and link. */
    private static function liveRecord(ChangeModel $entry): ?object
    {
        return once(function () use ($entry): ?object {
            $type = app(AuditableRecords::class)->findByKey($entry->record_type);

            return $type === null ? null : $type->modelClass::query()->find($entry->record_id);
        });
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
