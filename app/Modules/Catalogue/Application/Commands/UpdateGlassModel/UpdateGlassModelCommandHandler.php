<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\UpdateGlassModel;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Application\Services\GlassModelChoices;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\ValueObjects\OemCode;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** WF-03, FR-02: also while withdrawn; a withdrawn value already on the model stays. */
final class UpdateGlassModelCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private GlassModelRepository $glassModels,
        private GlassModelChoices $choices,
    ) {}

    #[CommandHandler]
    public function handle(UpdateGlassModelCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $oemCodes = array_values(array_filter(array_map(OemCode::tryFrom(...), $command->oemCodes)));
        $glassModel = $this->glassModels->get($command->glassModelId);

        $this->choices->ensureChoosable(
            $command->positionId,
            $command->colourId,
            $command->equipmentIds,
            array_values(array_filter([
                $glassModel->positionId(),
                $glassModel->colourId(),
                ...$glassModel->equipmentIds(),
            ])),
        );

        $changed = $glassModel->changeParameters($command->positionId, $command->colourId, $command->equipmentIds, $oemCodes);
        $this->glassModels->save($glassModel);

        if ($changed) {
            event(new GlassModelChanged($command->glassModelId, CarbonImmutable::now()));
        }
    }
}
