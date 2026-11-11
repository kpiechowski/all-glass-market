<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Tables;

use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\GlassModelQueries;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\DeleteGlassModelAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\RestoreGlassModelAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Actions\WithdrawGlassModelAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\GlassModelResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Template A (list), with the card layout of D-53: one full-width card per Glass model so results
 * compare side by side. FR-05: the search is the candidate search (ordered Eurocode, OEM, vehicle,
 * each card saying what it matched); without a text the list is plain. More than three filters, so
 * the funnel layout.
 */
class GlassModelsTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns(self::columns())
            ->modifyQueryUsing(fn (Builder $query, HasTable $livewire): Builder => self::withdrawnOnlyOnRequest($query, $livewire))
            ->filters(GlassModelsTableFilters::make())
            ->searchable()
            ->searchPlaceholder(__('catalogue::resources.glass_model.search.placeholder'))
            ->searchUsing(fn (Builder $query, string $search, HasTable $livewire): Builder => self::candidates($query, $search, $livewire))
            ->recordUrl(fn (GlassModelModel $record): string => GlassModelResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ActionGroup::make([
                    WithdrawGlassModelAction::make(),
                    RestoreGlassModelAction::make(),
                    DeleteGlassModelAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedRectangleStack,
            heading: __('catalogue::resources.glass_model.empty.heading'),
            description: __('catalogue::resources.glass_model.empty.description'),
            actions: [CreateAction::make()->label(__('catalogue::resources.glass_model.actions.create.label'))],
        );
    }

    /** @return array<mixed> */
    private static function columns(): array
    {
        return [
            GlassModelCardColumn::make(),
        ];
    }

    /** FR-05: withdrawn models are listed only while the toggle is on. */
    private static function withdrawnOnlyOnRequest(Builder $query, HasTable $livewire): Builder
    {
        return self::asksForWithdrawn($livewire) ? $query : app(GlassModelQueries::class)->onlyActive($query);
    }

    private static function asksForWithdrawn(HasTable $livewire): bool
    {
        return (bool) ($livewire->getTableFilterState(GlassModelsTableFilters::WITH_WITHDRAWN)['isActive'] ?? false);
    }

    /** The search narrows to the candidates, in their order; the table's filters are handed to it so the limit counts only what they let through. */
    private static function candidates(Builder $query, string $search, HasTable $livewire): Builder
    {
        $selected = fn (string $filter): ?int => filled($livewire->getTableFilterState($filter)['value'] ?? null)
            ? (int) $livewire->getTableFilterState($filter)['value']
            : null;

        return app(GlassModelQueries::class)->candidates(
            text: $search,
            positionId: $selected(GlassModelsTableFilters::POSITION),
            colourId: $selected(GlassModelsTableFilters::COLOUR),
            makeId: $selected(GlassModelsTableFilters::MAKE),
            includeWithdrawn: self::asksForWithdrawn($livewire),
            base: $query,
        );
    }
}
