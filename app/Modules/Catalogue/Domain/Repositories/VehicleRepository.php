<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Repositories;

use App\Core\Contracts\Repository;
use App\Modules\Catalogue\Domain\Entities\Vehicle;

/** @extends Repository<Vehicle> */
interface VehicleRepository extends Repository
{
    /**
     * RULE-09: whether a vehicle already has this name, ignoring letter case.
     * `$exceptId` leaves out the vehicle being changed.
     */
    public function nameIsTaken(string $name, ?int $exceptId = null): bool;
}
