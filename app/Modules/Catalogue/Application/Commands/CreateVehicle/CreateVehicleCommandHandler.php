<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\CreateVehicle;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Entities\Vehicle;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Repositories\DictionaryValueRepository;
use App\Modules\Catalogue\Domain\Repositories\VehicleRepository;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleDetails;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleName;
use Ecotone\Modelling\Attribute\CommandHandler;

final class CreateVehicleCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private VehicleRepository $vehicles,
        private DictionaryValueRepository $values,
    ) {}

    #[CommandHandler]
    public function handle(CreateVehicleCommand $command): int
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $name = VehicleName::from($command->name);
        $details = VehicleDetails::from($command->model, $command->yearFrom, $command->yearTo, $command->generation, $command->argicCode);

        $vehicle = Vehicle::create(
            $name,
            $details,
            $this->values->get($command->makeId),
            $command->bodyId === null ? null : $this->values->get($command->bodyId),
            $this->vehicles->nameIsTaken($name->value),
        );

        return $this->vehicles->add($vehicle);
    }
}
