<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Support;

use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use Closure;
use Filament\Schemas\Components\Component;

/**
 * FR-25: the extension point of a dictionary value's view. An integration registers a factory in
 * its provider's boot() and the view shows the section it returns, for example a producer's
 * status and external id on a channel:
 *
 *     $this->app->make(DictionaryValueViewSections::class)->register(
 *         fn (DictionaryValueModel $value): ?Component => $value->dictionary === Dictionary::Producer
 *             ? Section::make('WooCommerce')->schema([...]) : null,
 *     );
 *
 * Catalogue registers none and stores and shows nothing about channels (D-26, D-43).
 */
final class DictionaryValueViewSections
{
    /** @var list<Closure(DictionaryValueModel): ?Component> */
    private array $factories = [];

    /** @param  Closure(DictionaryValueModel): ?Component  $factory */
    public function register(Closure $factory): void
    {
        $this->factories[] = $factory;
    }

    /** @return list<Component> the sections to show for this value; a factory returning null adds none */
    public function for(DictionaryValueModel $value): array
    {
        return array_values(array_filter(array_map(
            fn (Closure $factory): ?Component => $factory($value),
            $this->factories,
        )));
    }
}
