<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\CreateVehicle;

use App\Core\Domain\Actor;

final readonly class CreateVehicleCommand
{
    public function __construct(
        public Actor $actor,
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
