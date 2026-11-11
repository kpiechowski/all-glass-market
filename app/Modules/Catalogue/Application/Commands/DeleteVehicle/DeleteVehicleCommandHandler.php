<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\DeleteVehicle;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\Repositories\VehicleRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

final class DeleteVehicleCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private VehicleRepository $vehicles,
        private GlassModelRepository $glassModels,
    ) {}

    #[CommandHandler]
    public function handle(DeleteVehicleCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $vehicle = $this->vehicles->get($command->vehicleId);

        /* WF-05: the models are collected before the delete removes the fitments (foreign key); a fitment never blocks it. */
        $glassModelIds = $this->glassModels->idsFittingVehicle($command->vehicleId);

        $this->vehicles->remove($vehicle);

        foreach ($glassModelIds as $glassModelId) {
            event(new GlassModelChanged($glassModelId, CarbonImmutable::now()));
        }
    }
}
