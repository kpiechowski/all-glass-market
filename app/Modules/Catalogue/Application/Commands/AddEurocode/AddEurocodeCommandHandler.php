<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\AddEurocode;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

final class AddEurocodeCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private GlassModelRepository $glassModels,
    ) {}

    #[CommandHandler]
    public function handle(AddEurocodeCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $eurocode = Eurocode::from($command->eurocode);
        $glassModel = $this->glassModels->get($command->glassModelId);

        $changed = $glassModel->addEurocode($eurocode, $this->glassModels->holderOfEurocode($eurocode, $command->glassModelId));

        if (! $changed) {
            return;
        }

        $this->glassModels->save($glassModel);

        event(new GlassModelChanged($command->glassModelId, CarbonImmutable::now()));
    }
}
