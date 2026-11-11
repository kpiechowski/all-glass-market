<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\CreateDictionaryValue;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Entities\DictionaryValue;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Repositories\DictionaryValueRepository;
use App\Modules\Catalogue\Domain\ValueObjects\DictionaryValueName;
use Ecotone\Modelling\Attribute\CommandHandler;

final class CreateDictionaryValueCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private DictionaryValueRepository $values,

    ) {}

    #[CommandHandler]
    public function handle(CreateDictionaryValueCommand $command): int
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $dictionary = Dictionary::from($command->dictionary);
        $name = DictionaryValueName::from($command->name);

        $value = DictionaryValue::create(
            $dictionary,
            $name,
            $this->values->nameIsTaken($dictionary, $name->value),
        );

        return $this->values->add($value);
    }
}
