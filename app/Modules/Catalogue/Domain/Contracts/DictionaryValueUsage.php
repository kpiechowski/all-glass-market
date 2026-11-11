<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Contracts;

use App\Modules\Catalogue\Domain\Enums\Dictionary;

/**
 * The seam of "delete only while unused" (FR-24, WF-04). Whoever keeps records that refer to a
 * dictionary value implements this and registers it with `DictionaryValueUsages` in its provider's
 * boot(): vehicles (make, body), Glass models (position, colour, equipment) inside Catalogue,
 * Offers (producer) from outside. A value with any use cannot be deleted, only withdrawn.
 */
interface DictionaryValueUsage
{
    /**
     * The uses of one value, empty when this implementation knows none. A use of another
     * dictionary is none: implementations answer only for the dictionaries they hold.
     *
     * @return list<DictionaryUse>
     */
    public function usesOf(Dictionary $dictionary, int $valueId): array;
}
