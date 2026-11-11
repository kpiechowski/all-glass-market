<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\DeleteVehicle;

use App\Core\Domain\Actor;

final readonly class DeleteVehicleCommand
{
    public function __construct(
        public Actor $actor,
        public int $vehicleId,
    ) {}
}
