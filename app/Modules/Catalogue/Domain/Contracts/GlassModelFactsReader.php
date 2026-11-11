<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Contracts;

/** Port behind the Glass model summary (FR-21): the facts of many models in a fixed number of reads. */
interface GlassModelFactsReader
{
    /**
     * @param  list<int>  $ids
     * @return list<GlassModelFacts> only the models that exist, in id order
     */
    public function read(array $ids): array;
}
