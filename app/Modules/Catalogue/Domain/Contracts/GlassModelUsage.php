<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Contracts;

/**
 * The seam of "delete a Glass model only while unused" (FR-24, WF-04). Whoever keeps records that
 * refer to a Glass model implements this and registers it with `GlassModelUsages` in its provider's
 * boot(): Offers (every offer it still keeps, whatever its state) in ticket 09. Nothing inside
 * Catalogue uses a Glass model yet.
 */
interface GlassModelUsage
{
    /** @return list<DictionaryUse> the uses of the model, empty when this implementation knows none */
    public function usesOf(int $glassModelId): array;
}
