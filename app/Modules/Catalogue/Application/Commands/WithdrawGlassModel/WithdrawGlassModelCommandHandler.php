<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\WithdrawGlassModel;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Events\GlassModelWithdrawn;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** WF-04, FR-06: announces the model as withdrawn (§9). */
final class WithdrawGlassModelCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private GlassModelRepository $glassModels,
    ) {}

    #[CommandHandler]
    public function handle(WithdrawGlassModelCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $glassModel = $this->glassModels->get($command->glassModelId);
        $glassModel->withdraw();
        $this->glassModels->save($glassModel);

        event(new GlassModelWithdrawn($command->glassModelId, CarbonImmutable::now()));
    }
}
