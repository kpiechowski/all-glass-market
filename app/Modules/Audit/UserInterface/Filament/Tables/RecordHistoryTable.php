<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Tables;

use App\Core\UserInterface\Filament\Tables\Columns\RecordCardColumn;
use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Audit\Application\Queries\PresentChange\ChangePresenter;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Audit\UserInterface\Filament\Actions\RestoreChangeAction;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

/**
 * The "Historia zmian" tab of a record view (template A inside C): when, kind, Actor, newest
 * first. No changed fields here (D-19); the row opens the details. No create, edit or delete.
 * The central history (`AuditLogTable`) reuses the columns and the details modal.
 */
class RecordHistoryTable
{
    public static function configure(Table $table, string $recordType, int $recordId): Table
    {
        $table = $table
            ->columns(self::columns())
            ->filters(RecordHistoryTableFilters::make($recordType, $recordId), layout: FiltersLayout::AboveContent)
            ->defaultSort('occurred_at', 'desc')
            ->recordAction('details')
            ->recordActions([self::restoreAction()])
            ->headerActions([])
            ->toolbarActions([]);

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedClock,
            heading: __('audit::resources.record_history.empty.heading'),
            description: __('audit::resources.record_history.empty.description'),
        );
    }

    /** @return list<Column> */
    private static function columns(): array
    {
        return [
            RecordCardColumn::make()
                ->title(fn (ChangeModel $record): string => self::kindLabel($record))
                ->subtitle(fn (ChangeModel $record): string => self::actorName($record))
                ->meta(fn (ChangeModel $record): array => [$record->occurred_at->format('d.m.Y H:i'), self::restoreMarker($record)]),
            self::occurredAtColumn(),
            self::kindColumn(),
            self::actorColumn(),
        ];
    }

    /** The first column; it carries the details modal, which the row click opens (A, Actions). */
    public static function occurredAtColumn(): TextColumn
    {
        return TextColumn::make('occurred_at')
            ->label(__('audit::resources.record_history.fields.occurred_at'))
            ->dateTime('d.m.Y H:i')
            ->sortable()
            ->action(self::detailsAction())
            ->visibleFrom('md');
    }

    /** What happened: a category, so a neutral badge; a restore shows its source under it. */
    public static function kindColumn(): TextColumn
    {
        return TextColumn::make('kind')
            ->label(__('audit::resources.record_history.fields.kind'))
            ->badge()
            ->color('gray')
            ->formatStateUsing(fn (ChangeModel $record): string => self::kindLabel($record))
            ->description(fn (ChangeModel $record): ?string => self::restoreMarker($record))
            ->visibleFrom('md');
    }

    public static function actorColumn(): TextColumn
    {
        return TextColumn::make('actor')
            ->label(__('audit::resources.record_history.fields.actor'))
            ->state(fn (ChangeModel $record): string => self::actorName($record))
            ->visibleFrom('md');
    }

    /** One row action, so it stands alone as an icon (A, Actions). */
    public static function restoreAction(): Action
    {
        return RestoreChangeAction::make()
            ->iconButton()
            ->tooltip(__('audit::resources.restore.label'));
    }

    public static function kindLabel(ChangeModel $record): string
    {
        return __('audit::enums.change_kind.'.$record->kind->value);
    }

    public static function restoreMarker(ChangeModel $record): ?string
    {
        return $record->restored_from_at === null
            ? null
            : __('audit::resources.restore.marker', ['date' => $record->restored_from_at->format('d.m.Y H:i')]);
    }

    public static function actorName(ChangeModel $record): string
    {
        if ($record->actor_id === null) {
            return __('audit::resources.record_history.system', [
                'reason' => app(ChangePresenter::class)->actorReason((string) $record->actor_reason),
            ]);
        }

        return $record->actor?->name ?? '#'.$record->actor_id;
    }

    /** Read-only details (E): no submit, "Zamknij". */
    public static function detailsAction(): Action
    {
        return Action::make('details')
            ->label(__('audit::resources.record_history.details'))
            ->modalHeading(__('audit::resources.record_history.details_heading'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('audit::resources.record_history.close'))
            ->modalContent(fn (ChangeModel $record): View => view('audit::change-details', [
                'entry' => $record,
                'actor' => self::actorName($record),
                'lines' => app(ChangePresenter::class)->present($record->record_type, $record->changes ?? [], $record->relation_changes ?? []),
            ]));
    }
}
