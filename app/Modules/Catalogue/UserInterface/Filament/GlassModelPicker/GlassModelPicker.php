<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker;

use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\GlassModelQueries;
use App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker\Actions\CreateGlassModelInPlaceAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Tables\GlassModelsTable;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Tables\GlassModelsTableFilters;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Component;

/**
 * The Glass model picker (FR-19, FR-20, WF-06): the catalogue card list of the admin screen with
 * its search and filters, in which the user marks one card. Catalogue knows nothing of Offers; this
 * is the seam an embedding module uses (see `GlassModelPickerField` and `GlassModelPickerSlideOver`):
 *
 * - **Result out:** `$selectedId` is `#[Modelable]`, so `wire:model` on the component gives the
 *   embedder the marked Glass model id (null while nothing is marked). It is client state: the
 *   embedder's command must check the model exists and is not withdrawn.
 * - **Prefill in:** `$prefill` is the search text the picker opens with (Offers' identification hint).
 * - **Variant in:** `Seller` lists no withdrawn models and offers no creation; `Admin` has the
 *   "with withdrawn" toggle and in-place creation (WF-01). Locked, and an Admin variant is honoured only
 *   for a user who may manage the catalogue.
 *
 * The cards are the ones of issue 11 (`GlassModelsTable::columns()`); the picker only adds the marking.
 */
class GlassModelPicker extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    #[Modelable]
    public ?int $selectedId = null;

    #[Locked]
    public string $variant = 'seller';

    #[Locked]
    public ?string $prefill = null;

    public function mount(?string $prefill = null, ?string $variant = null): void
    {
        $this->prefill = filled($prefill) ? $prefill : null;
        $this->variant = ($variant === null ? PickerVariant::forCurrentActor() : PickerVariant::from($variant))->effective()->value;
        $this->tableSearch = (string) $this->prefill;
    }

    public function isAdminVariant(): bool
    {
        return PickerVariant::from($this->variant)->effective() === PickerVariant::Admin;
    }

    /** Marks the card; marking the marked card again clears it. Only a model this picker lists can be marked. */
    public function mark(int $glassModelId): void
    {
        if ($this->selectedId === $glassModelId) {
            $this->selectedId = null;

            return;
        }

        if ($this->markableModels()->whereKey($glassModelId)->exists()) {
            $this->selectedId = $glassModelId;
        }
    }

    /** What this picker may list at all: withdrawn models only for the Admin variant. */
    public function markableModels(): Builder
    {
        $queries = app(GlassModelQueries::class);

        return $this->isAdminVariant() ? $queries->listing() : $queries->onlyActive($queries->listing());
    }

    /** The picker marks a model it has just created and shows it. */
    public function markCreated(int $glassModelId, string $mainEurocode): void
    {
        $this->selectedId = $glassModelId;
        $this->tableSearch = $mainEurocode;
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        $admin = $this->isAdminVariant();

        return $table
            ->query(fn (): Builder => $this->markableModels())
            ->columns(array_map(
                fn ($card) => $card->action(fn (GlassModelModel $record) => $this->mark((int) $record->getKey())),
                GlassModelsTable::columns(withLinks: false),
            ))
            ->filters(GlassModelsTableFilters::make(withWithdrawnToggle: $admin))
            ->modifyQueryUsing(fn (Builder $query): Builder => $admin && $this->asksForWithdrawn() ? $query : app(GlassModelQueries::class)->onlyActive($query))
            ->searchable()
            ->searchPlaceholder(__('catalogue::resources.glass_model.search.placeholder'))
            ->searchUsing(fn (Builder $query, string $search): Builder => GlassModelsTable::candidates($query, $search, $this, $admin && $this->asksForWithdrawn()))
            ->recordClasses(fn (GlassModelModel $record): ?string => $record->getKey() === $this->selectedId ? 'glass-card-row--marked' : null)
            ->headerActions($admin ? [CreateGlassModelInPlaceAction::make()] : [])
            ->emptyStateHeading(__('catalogue::resources.glass_model_picker.empty.heading'))
            ->emptyStateDescription(__('catalogue::resources.glass_model_picker.empty.description'))
            ->defaultSort('id', 'desc');
    }

    public function render(): View
    {
        return view('catalogue::filament.glass-model-picker.picker');
    }

    private function asksForWithdrawn(): bool
    {
        return (bool) ($this->getTableFilterState(GlassModelsTableFilters::WITH_WITHDRAWN)['isActive'] ?? false);
    }
}
