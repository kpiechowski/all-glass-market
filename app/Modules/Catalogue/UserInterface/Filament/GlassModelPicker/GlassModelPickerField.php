<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\GlassModelPicker;

use Closure;
use Filament\Forms\Components\Field;

/**
 * The Glass model picker as a form field (FR-19): its state is the marked Glass model id, or null.
 * Any form, action modal or bulk-action modal of another module can hold it; Catalogue knows nothing
 * of that module. See `GlassModelPickerSlideOver` to open it in a slide-over from an action.
 *
 *     GlassModelPickerField::make('glass_model_id')->search($offer->hint)->required()
 */
class GlassModelPickerField extends Field
{
    protected string $view = 'catalogue::filament.glass-model-picker.field';

    protected string|Closure|null $search = null;

    protected PickerVariant|Closure|null $variant = null;

    /** The text the picker opens searching for (Offers' identification hint). */
    public function search(string|Closure|null $search): static
    {
        $this->search = $search;

        return $this;
    }

    /** Defaults to the variant that fits the logged-in user. */
    public function variant(PickerVariant|Closure|null $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getSearch(): ?string
    {
        $search = $this->evaluate($this->search);

        return filled($search) ? (string) $search : null;
    }

    public function getVariant(): PickerVariant
    {
        return $this->evaluate($this->variant) ?? PickerVariant::forCurrentActor();
    }
}
