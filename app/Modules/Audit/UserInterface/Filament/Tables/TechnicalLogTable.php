<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Tables;

use App\Modules\Audit\Domain\Enums\LogLevel;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * One day's entries of one channel, newest first (FR-16). Custom data: the page
 * supplies `records()`. Lean on purpose: time, level, the message cut to a line.
 * No create, edit or delete (D-29).
 */
class TechnicalLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->filters(TechnicalLogTableFilters::make(), layout: FiltersLayout::AboveContentCollapsible)
            ->searchable()
            ->recordActions([self::detailsAction()])
            ->recordAction('details')
            ->headerActions([])
            ->toolbarActions([])
            ->emptyStateHeading(__('audit::pages.technical_log.empty'))
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    /** @return list<TextColumn> */
    private static function columns(): array
    {
        return [
            TextColumn::make('time')
                ->label(__('audit::pages.technical_log.fields.time')),
            TextColumn::make('level')
                ->label(__('audit::pages.technical_log.fields.level'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => LogLevel::tryFrom($state)?->getLabel() ?? $state)
                ->color(fn (string $state): string => LogLevel::tryFrom($state)?->getColor() ?? 'gray'),
            TextColumn::make('summary')
                ->label(__('audit::pages.technical_log.fields.message'))
                ->limit(120),
        ];
    }

    private static function detailsAction(): Action
    {
        return Action::make('details')
            ->label(__('audit::pages.technical_log.details'))
            ->icon('heroicon-o-eye')
            ->modalHeading(__('audit::pages.technical_log.details_heading'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('audit::pages.technical_log.close'))
            ->modalWidth('5xl')
            ->modalContent(fn (?array $record) => $record === null ? null : view('audit::log-entry-details', [
                'entry' => $record,
                'levelLabel' => LogLevel::tryFrom($record['level'])?->getLabel() ?? Str::upper($record['level']),
            ]));
    }
}
