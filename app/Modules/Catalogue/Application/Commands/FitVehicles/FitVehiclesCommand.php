<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\FitVehicles;

use App\Core\Domain\Actor;

final readonly class FitVehiclesCommand
{
    /** @param  list<int>  $vehicleIds */
    public function __construct(
        public Actor $actor,
        public int $glassModelId,
        public array $vehicleIds,
    ) {}
}
