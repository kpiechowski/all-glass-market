<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\RestoreGlassModel;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Events\GlassModelRestored;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** WF-04, FR-06: announces the model as restored (§9). */
final class RestoreGlassModelCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private GlassModelRepository $glassModels,
    ) {}

    #[CommandHandler]
    public function handle(RestoreGlassModelCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $glassModel = $this->glassModels->get($command->glassModelId);
        $glassModel->restore();
        $this->glassModels->save($glassModel);

        event(new GlassModelRestored($command->glassModelId, CarbonImmutable::now()));
    }
}
