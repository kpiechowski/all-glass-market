<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\CreateGlassModel;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Application\Services\GlassModelChoices;
use App\Modules\Catalogue\Domain\Entities\GlassModel;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use App\Modules\Catalogue\Domain\ValueObjects\OemCode;
use Ecotone\Modelling\Attribute\CommandHandler;

/** WF-01. No event: a Glass model is announced as changed on every change but its creation (§9). */
final class CreateGlassModelCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private GlassModelRepository $glassModels,
        private GlassModelChoices $choices,
    ) {}

    #[CommandHandler]
    public function handle(CreateGlassModelCommand $command): int
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $eurocode = Eurocode::from($command->mainEurocode);
        $oemCodes = array_values(array_filter(array_map(OemCode::tryFrom(...), $command->oemCodes)));

        $glassModel = GlassModel::create(
            $eurocode,
            $command->positionId,
            $command->colourId,
            $command->equipmentIds,
            $oemCodes,
            $this->glassModels->holderOfEurocode($eurocode),
        );

        foreach ($command->furtherEurocodes as $typed) {
            $further = Eurocode::from($typed);
            $glassModel->addEurocode($further, $this->glassModels->holderOfEurocode($further));
        }

        $this->choices->ensureChoosable($command->positionId, $command->colourId, $command->equipmentIds);

        return $this->glassModels->add($glassModel);
    }
}
