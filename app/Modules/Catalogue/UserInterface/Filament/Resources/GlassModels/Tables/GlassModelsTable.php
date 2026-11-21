<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Tables;

use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelCardLink;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelLinks;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelThumbnails;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\GlassModelQueries;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\GlassModelResource;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use WeakMap;

/**
 * Template A (list), with the card layout of D-53: one full-width card per Glass model so results
 * compare side by side. FR-05: the search is the candidate search (ordered Eurocode, OEM, vehicle,
 * each card saying what it matched); without a text the list is plain. More than three filters, so
 * the funnel layout. The row click opens the record, where every action lives; no row-actions menu.
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
            ->searchUsing(fn (Builder $query, string $search, HasTable $livewire): Builder => self::candidates($query, $search, $livewire, self::asksForWithdrawn($livewire)))
            ->recordUrl(fn (GlassModelModel $record): string => GlassModelResource::getUrl('view', ['record' => $record]))
            ->defaultSort('id', 'desc');

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedRectangleStack,
            heading: __('catalogue::resources.glass_model.empty.heading'),
            description: __('catalogue::resources.glass_model.empty.description'),
            actions: [CreateAction::make()->label(__('catalogue::resources.glass_model.actions.create.label'))],
        );
    }

    /** The card column, shared with the Glass model picker (FR-19), which lists the same cards. @return array<mixed> */
    public static function columns(bool $withLinks = true): array
    {
        return [
            GlassModelCardColumn::make()
                ->offersLink($withLinks ? self::links(...) : null)
                ->thumbnail(fn (GlassModelModel $record): Htmlable => view('catalogue::components.glass-thumbnail', [
                    'thumbnail' => app(GlassModelThumbnails::class)->for(
                        $record->position_id,
                        $record->colour_id,
                        $record->equipment->pluck('name', 'id')->all(),
                    ),
                ])),
        ];
    }

    /**
     * The links other modules add to a card ("N ofert"), read once for the whole page. Kept per table
     * component, which lives for one render, so the next render reads afresh.
     */
    private static function links(GlassModelModel $record, HasTable $livewire): ?Htmlable
    {
        static $pages = null;
        $pages ??= new WeakMap;
        $pages[$livewire] ??= app(GlassModelLinks::class)->ofMany(array_map('intval', $livewire->getTableRecords()->modelKeys()));

        $links = array_map(
            fn (GlassModelCardLink $link): string => '<a href="'.e($link->url).'" class="fi-link" x-on:click.stop>'.e($link->label).'</a>',
            $pages[$livewire][(int) $record->getKey()] ?? [],
        );

        return $links === [] ? null : new HtmlString(implode(' · ', $links));
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
    public static function candidates(Builder $query, string $search, HasTable $livewire, bool $includeWithdrawn): Builder
    {
        $selected = fn (string $filter): ?int => filled($livewire->getTableFilterState($filter)['value'] ?? null)
            ? (int) $livewire->getTableFilterState($filter)['value']
            : null;

        return app(GlassModelQueries::class)->candidates(
            text: $search,
            positionId: $selected(GlassModelsTableFilters::POSITION),
            colourId: $selected(GlassModelsTableFilters::COLOUR),
            makeId: $selected(GlassModelsTableFilters::MAKE),
            includeWithdrawn: $includeWithdrawn,
            base: $query,
        );
    }
}
