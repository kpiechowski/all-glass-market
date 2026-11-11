<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Repositories;

use App\Core\Contracts\Repository;
use App\Modules\Catalogue\Domain\Entities\GlassModel;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use App\Modules\Catalogue\Domain\ValueObjects\EurocodeHolder;

/** @extends Repository<GlassModel> */
interface GlassModelRepository extends Repository
{
    /** RULE-01: the model holding the Eurocode, withdrawn models included; `$exceptId` leaves out the model being changed. */
    public function holderOfEurocode(Eurocode $eurocode, ?int $exceptId = null): ?EurocodeHolder;

    /**
     * FR-14: the ids of every Glass model using a position, colour or equipment value.
     *
     * @return list<int>
     */
    public function idsUsing(Dictionary $dictionary, int $valueId): array;
}
