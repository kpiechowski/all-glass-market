<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\MoveEurocode;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

final class MoveEurocodeCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private GlassModelRepository $glassModels,
    ) {}

    #[CommandHandler]
    public function handle(MoveEurocodeCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        if ($command->glassModelId === $command->targetGlassModelId) {
            throw GlassModelRefused::cannotMoveToItself();
        }

        $eurocode = Eurocode::from($command->eurocode);
        $source = $this->glassModels->get($command->glassModelId);
        $target = $this->glassModels->get($command->targetGlassModelId);

        $source->removeEurocode($eurocode);
        $target->receiveEurocode($eurocode);

        $this->glassModels->save($source);
        $this->glassModels->save($target);

        $now = CarbonImmutable::now();
        event(new GlassModelChanged($command->glassModelId, $now));
        event(new GlassModelChanged($command->targetGlassModelId, $now));
    }
}
