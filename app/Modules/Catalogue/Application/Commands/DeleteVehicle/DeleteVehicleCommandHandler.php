<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\DeleteVehicle;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Repositories\VehicleRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

final class DeleteVehicleCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private VehicleRepository $vehicles,
    ) {}

    #[CommandHandler]
    public function handle(DeleteVehicleCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        /* FR-11 / WF-05: ticket 05 adds the fitment count warning, the removal of fitments and the "Glass model changed" announcements here. */
        $this->vehicles->remove($this->vehicles->get($command->vehicleId));
    }
}
