<?php

declare(strict_types=1);

namespace App\Filament\Tables;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

/**
 * Panel-wide table defaults (template A, D-14), registered through `Table::configureUsing()` in
 * `AdminPanelProvider::boot()`. A table may still override each of them.
 *
 * - Inline filters (`FiltersLayout::AboveContent`) apply on change; panel and modal filters keep
 *   the "Zastosuj filtry" button.
 * - A one-column filter panel is 24 rem wide instead of 20 rem.
 * - The filter trigger carries `data-active-filters`, so the theme hides the count badge at 0.
 * - "Zresetuj filtry" shows only while a filter is active.
 */
final class TableDefaults
{
    public static function apply(Table $table): void
    {
        $table
            ->deferFilters(fn (Table $table): bool => $table->getFiltersLayout() !== FiltersLayout::AboveContent)
            ->filtersFormWidth(fn (Table $table): ?Width => $table->getFiltersFormColumns() === 1 ? Width::Small : null)
            ->filtersTriggerAction(fn (Action $action): Action => $action->extraAttributes(
                fn (Table $table): array => ['data-active-filters' => (string) $table->getActiveFiltersCount()],
                merge: true,
            ))
            ->filtersResetAction(fn (Action $action): Action => $action->visible(fn (Table $table): bool => $table->isFiltered()));
    }
}
