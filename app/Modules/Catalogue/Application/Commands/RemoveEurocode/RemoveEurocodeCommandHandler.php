<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\RemoveEurocode;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

final class RemoveEurocodeCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private GlassModelRepository $glassModels,
    ) {}

    #[CommandHandler]
    public function handle(RemoveEurocodeCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $glassModel = $this->glassModels->get($command->glassModelId);

        $glassModel->removeEurocode(Eurocode::from($command->eurocode));
        $this->glassModels->save($glassModel);

        event(new GlassModelChanged($command->glassModelId, CarbonImmutable::now()));
    }
}
