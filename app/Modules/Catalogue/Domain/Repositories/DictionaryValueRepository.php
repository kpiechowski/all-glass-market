<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Repositories;

use App\Core\Contracts\Repository;
use App\Modules\Catalogue\Domain\Entities\DictionaryValue;
use App\Modules\Catalogue\Domain\Enums\Dictionary;

/** @extends Repository<DictionaryValue> */
interface DictionaryValueRepository extends Repository
{
    /**
     * RULE-08: whether a value of the dictionary already has this name, ignoring letter case.
     * `$exceptId` leaves out the value being renamed.
     */
    public function nameIsTaken(Dictionary $dictionary, string $name, ?int $exceptId = null): bool;
}
