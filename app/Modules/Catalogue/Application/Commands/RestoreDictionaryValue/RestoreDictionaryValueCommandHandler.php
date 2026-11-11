<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\RestoreDictionaryValue;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Repositories\DictionaryValueRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

final class RestoreDictionaryValueCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private DictionaryValueRepository $values,

    ) {}

    #[CommandHandler]
    public function handle(RestoreDictionaryValueCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $value = $this->values->get($command->valueId);
        $value->restore();
        $this->values->save($value);
    }
}
