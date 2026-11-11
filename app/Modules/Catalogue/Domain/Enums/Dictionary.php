<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The six dictionaries of Catalogue (FR-12). They share one entity and one table; the case is the
 * only thing telling them apart. The values are stored: never rename them.
 */
enum Dictionary: string implements HasLabel
{
    case Position = 'position';
    case Colour = 'colour';
    case Equipment = 'equipment';
    case Producer = 'producer';
    case VehicleMake = 'vehicle_make';
    case VehicleBody = 'vehicle_body';

    public function getLabel(): string
    {
        return __('catalogue::enums.dictionary.'.$this->value);
    }

    /** Label of the dictionary as a list: "Rodzaje szyb". */
    public function pluralLabel(): string
    {
        return __('catalogue::enums.dictionary_plural.'.$this->value);
    }
}
