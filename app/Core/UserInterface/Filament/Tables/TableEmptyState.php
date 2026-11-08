<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Tables;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * The two empty states of a list table (D-15, template A):
 *
 * - no records: the table's own icon, one sentence and its primary action;
 * - no results under a search or filter: a magnifier, "Brak wyników dla tych filtrów" and
 *   "Wyczyść filtry", which clears the filters and the search. The primary action hides.
 */
final class TableEmptyState
{
    /**
     * @param  list<Action>  $actions  the primary action when the user may perform it (its own `visible()` still applies)
     */
    public static function configure(Table $table, string|BackedEnum $icon, string $heading, string $description, array $actions = []): Table
    {
        return $table
            ->emptyStateIcon(fn (Table $table): string|BackedEnum => self::isNarrowed($table) ? Heroicon::OutlinedMagnifyingGlass : $icon)
            ->emptyStateHeading(fn (Table $table): string => self::isNarrowed($table) ? __('core::tables.empty.filtered_heading') : $heading)
            ->emptyStateDescription(fn (Table $table): string => self::isNarrowed($table) ? __('core::tables.empty.filtered_description') : $description)
            ->emptyStateActions([
                ...array_map(
                    fn (Action $action): Action => $action->hidden(fn (Table $table): bool => self::isNarrowed($table)),
                    $actions,
                ),
                Action::make('clearTableFiltersFromEmptyState')
                    ->label(__('core::tables.empty.clear_filters'))
                    ->color('gray')
                    ->visible(fn (Table $table): bool => self::isNarrowed($table))
                    ->action(fn (Table $table) => $table->getLivewire()->removeTableFilters()),
            ]);
    }

    /** True when a search or a filter narrows the rows. */
    public static function isNarrowed(Table $table): bool
    {
        return $table->isFiltered() || filled($table->getLivewire()->getTableSearch());
    }
}
