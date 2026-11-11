<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\FitVehicles;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\Repositories\VehicleRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-10: fitment belongs to the Glass model. */
final class FitVehiclesCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private GlassModelRepository $glassModels,
        private VehicleRepository $vehicles,
    ) {}

    #[CommandHandler]
    public function handle(FitVehiclesCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $glassModel = $this->glassModels->get($command->glassModelId);

        foreach ($command->vehicleIds as $vehicleId) {
            $this->vehicles->get($vehicleId);
        }

        if (! $glassModel->fitVehicles($command->vehicleIds)) {
            return;
        }

        $this->glassModels->save($glassModel);

        event(new GlassModelChanged($command->glassModelId, CarbonImmutable::now()));
    }
}
