<?php

declare(strict_types=1);

namespace App\Modules\Audit\UserInterface\Filament\Tables;

use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Audit\Domain\Enums\LogLevel;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * The "Logi techniczne" tab of "Historia i logi" (template A, technical table, FR-16): one day's
 * entries of one channel, newest first. Custom data: the page supplies `records()`. Channel, day
 * and level are the three inline filters; the row opens the details. No create, edit or delete (D-29).
 */
class TechnicalLogTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns(self::columns())
            ->filters(TechnicalLogTableFilters::make(), layout: FiltersLayout::AboveContent)
            ->searchable()
            ->recordAction('details')
            ->headerActions([])
            ->toolbarActions([])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedCommandLine,
            heading: __('audit::pages.history_and_logs.technical_logs.empty.heading'),
            description: __('audit::pages.history_and_logs.technical_logs.empty.description'),
        );
    }

    /**
     * Technical table: no card, every column on every width, the long text last.
     *
     * @return list<TextColumn>
     */
    private static function columns(): array
    {
        return [
            TextColumn::make('time')
                ->label(__('audit::pages.history_and_logs.technical_logs.fields.time'))
                ->dateTime('H:i:s')
                ->action(self::detailsAction()),
            TextColumn::make('level')
                ->label(__('audit::pages.history_and_logs.technical_logs.fields.level'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => LogLevel::tryFrom($state)?->getLabel() ?? $state)
                ->color(fn (string $state): string => LogLevel::tryFrom($state)?->getColor() ?? 'gray'),
            TextColumn::make('summary')
                ->label(__('audit::pages.history_and_logs.technical_logs.fields.message'))
                ->limit(120),
        ];
    }

    /** Read-only details (E): no submit, "Zamknij". */
    private static function detailsAction(): Action
    {
        return Action::make('details')
            ->label(__('audit::pages.history_and_logs.details'))
            ->modalHeading(__('audit::pages.history_and_logs.technical_logs.details_heading'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('audit::pages.history_and_logs.close'))
            ->modalWidth('5xl')
            ->modalContent(fn (?array $record): ?View => $record === null ? null : view('audit::log-entry-details', [
                'entry' => $record,
                'levelLabel' => LogLevel::tryFrom($record['level'])?->getLabel() ?? Str::upper($record['level']),
            ]));
    }
}
