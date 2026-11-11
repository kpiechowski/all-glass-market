<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\UpdateVehicle;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Repositories\DictionaryValueRepository;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\Repositories\VehicleRepository;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleDetails;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleName;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

final class UpdateVehicleCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private VehicleRepository $vehicles,
        private DictionaryValueRepository $values,
        private GlassModelRepository $glassModels,
    ) {}

    #[CommandHandler]
    public function handle(UpdateVehicleCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $name = VehicleName::from($command->name);
        $details = VehicleDetails::from($command->model, $command->yearFrom, $command->yearTo, $command->generation, $command->argicCode);
        $vehicle = $this->vehicles->get($command->vehicleId);

        $changed = $vehicle->change(
            $name,
            $details,
            $this->values->get($command->makeId),
            $command->bodyId === null ? null : $this->values->get($command->bodyId),
            $this->vehicles->nameIsTaken($name->value, $command->vehicleId),
        );
        $this->vehicles->save($vehicle);

        if (! $changed) {
            return;
        }

        /* §9: every Glass model the vehicle fits is announced as changed. */
        foreach ($this->glassModels->idsFittingVehicle($command->vehicleId) as $glassModelId) {
            event(new GlassModelChanged($glassModelId, CarbonImmutable::now()));
        }
    }
}
