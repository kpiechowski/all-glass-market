<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Tables;

use App\Modules\Audit\Application\Queries\PresentChange\ChangePresenter;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** A record's entries, newest first: when, Actor, kind. No changed fields here (D-19); no create, edit or delete. */
class RecordHistoryTable
{
    public static function configure(Table $table, string $recordType, int $recordId): Table
    {
        return $table
            ->columns(self::columns())
            ->filters(RecordHistoryTableFilters::make($recordType, $recordId))
            ->defaultSort('occurred_at', 'desc')
            ->recordActions([self::detailsAction()])
            ->recordAction('details')
            ->headerActions([])
            ->toolbarActions([]);
    }

    /** @return list<TextColumn> */
    public static function columns(): array
    {
        return [
            TextColumn::make('occurred_at')
                ->label(__('audit::resources.record_history.fields.occurred_at'))
                ->dateTime('d.m.Y H:i')
                ->sortable(),
            TextColumn::make('actor')
                ->label(__('audit::resources.record_history.fields.actor'))
                ->state(fn (ChangeModel $record): string => self::actorName($record)),
            TextColumn::make('kind')
                ->label(__('audit::resources.record_history.fields.kind'))
                ->badge()
                ->formatStateUsing(fn (ChangeModel $record): string => __('audit::enums.change_kind.'.$record->kind->value)),
        ];
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

    public static function detailsAction(): Action
    {
        return Action::make('details')
            ->label(__('audit::resources.record_history.details'))
            ->icon('heroicon-o-eye')
            ->modalHeading(fn (ChangeModel $record): string => __('audit::resources.record_history.details_heading'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('audit::resources.record_history.close'))
            ->modalContent(fn (ChangeModel $record) => view('audit::change-details', [
                'entry' => $record,
                'actor' => self::actorName($record),
                'lines' => app(ChangePresenter::class)->present($record->record_type, $record->changes ?? [], $record->relation_changes ?? []),
            ]));
    }
}
