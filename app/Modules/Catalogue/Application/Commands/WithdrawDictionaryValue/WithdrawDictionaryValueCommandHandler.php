<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\WithdrawDictionaryValue;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Events\ProducerWithdrawn;
use App\Modules\Catalogue\Domain\Repositories\DictionaryValueRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

final class WithdrawDictionaryValueCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private DictionaryValueRepository $values,

    ) {}

    #[CommandHandler]
    public function handle(WithdrawDictionaryValueCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        $value = $this->values->get($command->valueId);
        $value->withdraw();
        $this->values->save($value);

        /* WF-04: only a producer is announced; withdrawing another value changes no Glass model. */
        if ($value->dictionary() === Dictionary::Producer) {
            event(new ProducerWithdrawn($command->valueId, CarbonImmutable::now()));
        }
    }
}
