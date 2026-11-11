<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\RenameDictionaryValue;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Events\ProducerChanged;
use App\Modules\Catalogue\Domain\Repositories\DictionaryValueRepository;
use App\Modules\Catalogue\Domain\Repositories\GlassModelRepository;
use App\Modules\Catalogue\Domain\ValueObjects\DictionaryValueName;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

final class RenameDictionaryValueCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private DictionaryValueRepository $values,
        private GlassModelRepository $glassModels,
    ) {}

    #[CommandHandler]
    public function handle(RenameDictionaryValueCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $name = DictionaryValueName::from($command->name);
        $value = $this->values->get($command->valueId);

        $changed = $value->rename(
            $name,
            $this->values->nameIsTaken($value->dictionary(), $name->value, $command->valueId),
        );
        $this->values->save($value);

        if (! $changed) {
            return;
        }

        if ($value->dictionary() === Dictionary::Producer) {
            event(new ProducerChanged($command->valueId, CarbonImmutable::now()));
        }

        /* FR-14: a renamed position, colour or equipment value announces every Glass model using it. */
        foreach ($this->glassModels->idsUsing($value->dictionary(), $command->valueId) as $glassModelId) {
            event(new GlassModelChanged($glassModelId, CarbonImmutable::now()));
        }
    }
}
