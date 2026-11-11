<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Infrastructure\Persistence;

use App\Core\Abstracts\EloquentRepository;
use App\Modules\Catalogue\Domain\Entities\Vehicle;
use App\Modules\Catalogue\Domain\Repositories\VehicleRepository;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleName;
use App\Modules\Catalogue\Infrastructure\Persistence\Mappers\VehicleMapper;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;

/** @extends EloquentRepository<Vehicle> */
final class EloquentVehicleRepository extends EloquentRepository implements VehicleRepository
{
    public function __construct(VehicleMapper $mapper)
    {
        parent::__construct(VehicleModel::class, $mapper);
    }

    public function nameIsTaken(string $name, ?int $exceptId = null): bool
    {
        return $this->query()
            ->where('name_key', VehicleName::from($name)->key())
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }
}
