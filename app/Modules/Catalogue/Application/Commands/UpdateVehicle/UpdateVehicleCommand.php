<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\UpdateVehicle;

use App\Core\Domain\Actor;

final readonly class UpdateVehicleCommand
{
    public function __construct(
        public Actor $actor,
        public int $vehicleId,
        public string $name,
        public int $makeId,
        public ?string $model = null,
        public ?int $bodyId = null,
        public ?int $yearFrom = null,
        public ?int $yearTo = null,
        public ?string $generation = null,
        public ?string $argicCode = null,
    ) {}
}
