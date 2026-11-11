<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\DeleteDictionaryValue;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Application\ModuleApi\DictionaryValueUsages;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Repositories\DictionaryValueRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

final class DeleteDictionaryValueCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private DictionaryValueRepository $values,
        private DictionaryValueUsages $usages,
    ) {}

    #[CommandHandler]
    public function handle(DeleteDictionaryValueCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $value = $this->values->get($command->valueId);
        $value->ensureCanBeDeleted($this->usages->of($value->dictionary(), $command->valueId));

        $this->values->remove($value);
    }
}
