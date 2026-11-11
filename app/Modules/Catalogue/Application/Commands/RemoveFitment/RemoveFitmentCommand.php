<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\RemoveFitment;

use App\Core\Domain\Actor;

final readonly class RemoveFitmentCommand
{
    public function __construct(
        public Actor $actor,
        public int $glassModelId,
        public int $vehicleId,
    ) {}
}
