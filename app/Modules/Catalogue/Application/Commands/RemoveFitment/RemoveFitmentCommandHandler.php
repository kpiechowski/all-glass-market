<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\RemoveFitment;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-10: removes one vehicle from the Glass model's fitment. */
final class RemoveFitmentCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private GlassModelRepository $glassModels,
    ) {}

    #[CommandHandler]
    public function handle(RemoveFitmentCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $glassModel = $this->glassModels->get($command->glassModelId);
        $glassModel->removeFitment($command->vehicleId);
        $this->glassModels->save($glassModel);

        event(new GlassModelChanged($command->glassModelId, CarbonImmutable::now()));
    }
}
