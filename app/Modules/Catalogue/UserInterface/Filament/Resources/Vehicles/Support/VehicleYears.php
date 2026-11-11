<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Support;

use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;

/** How the production years read on screen: "2008–2015", "2008–" for an open end. */
final class VehicleYears
{
    public static function label(VehicleModel $vehicle): ?string
    {
        if ($vehicle->year_from === null && $vehicle->year_to === null) {
            return null;
        }

        return ($vehicle->year_from ?? '').'–'.($vehicle->year_to ?? '');
    }
}
