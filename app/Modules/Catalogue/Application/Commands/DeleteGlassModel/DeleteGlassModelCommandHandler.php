<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\DeleteGlassModel;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelUsages;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/** WF-04, FR-24: only while nothing uses the model; no announcement needed (§7). */
final class DeleteGlassModelCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private GlassModelRepository $glassModels,
        private GlassModelUsages $usages,
    ) {}

    #[CommandHandler]
    public function handle(DeleteGlassModelCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $glassModel = $this->glassModels->get($command->glassModelId);
        $glassModel->ensureCanBeDeleted($this->usages->of($command->glassModelId));

        $this->glassModels->remove($glassModel);
    }
}
