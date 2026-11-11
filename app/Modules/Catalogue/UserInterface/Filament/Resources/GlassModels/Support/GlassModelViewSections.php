<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Support;

use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use Closure;
use Filament\Schemas\Components\Component;

/**
 * FR-25: the extension point of a Glass model's view. An integration registers a factory in its
 * provider's boot() and the view shows the section it returns, for example the model's adverts on a
 * channel. Catalogue registers none and stores and shows nothing about channels (D-26, D-43).
 */
final class GlassModelViewSections
{
    /** @var list<Closure(GlassModelModel): ?Component> */
    private array $factories = [];

    /** @param  Closure(GlassModelModel): ?Component  $factory */
    public function register(Closure $factory): void
    {
        $this->factories[] = $factory;
    }

    /** @return list<Component> the sections to show for this model; a factory returning null adds none */
    public function for(GlassModelModel $model): array
    {
        return array_values(array_filter(array_map(
            fn (Closure $factory): ?Component => $factory($model),
            $this->factories,
        )));
    }
}
