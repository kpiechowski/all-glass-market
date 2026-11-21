<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Tables;

use Closure;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Contracts\Support\Htmlable;

/**
 * The catalogue row of a Glass model (FR-04, D-53): three column tracks (picture, main, side) on
 * fixed widths so rows line up. The only column of the list; it shows at every width.
 * Styles: components/glass-card.css.
 *
 * Two slots: `thumbnail()` (the equipment pictograms) and `offersLink()` (the links other modules
 * add, "N ofert", through `GlassModelLinks`). Each takes a closure with `$record`
 * returning Htmlable or null; a slot that returns nothing is not drawn.
 */
class GlassModelCardColumn extends ViewColumn
{
    protected string $view = 'catalogue::filament.tables.glass-model-card';

    protected ?Closure $thumbnailUsing = null;

    protected ?Closure $offersLinkUsing = null;

    public static function make(?string $name = 'card'): static
    {
        return parent::make($name ?? 'card');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('catalogue::resources.glass_model.plural_label'));
        $this->extraCellAttributes(['class' => 'glass-card-cell'], merge: true);
    }

    public function thumbnail(?Closure $using): static
    {
        $this->thumbnailUsing = $using;

        return $this;
    }

    public function offersLink(?Closure $using): static
    {
        $this->offersLinkUsing = $using;

        return $this;
    }

    public function getThumbnail(): ?Htmlable
    {
        return $this->thumbnailUsing === null ? null : $this->evaluate($this->thumbnailUsing);
    }

    public function getOffersLink(): ?Htmlable
    {
        return $this->offersLinkUsing === null ? null : $this->evaluate($this->offersLinkUsing);
    }
}
