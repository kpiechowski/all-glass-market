<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Contracts;

use App\Modules\Catalogue\Domain\Enums\CatalogueSeedSize;

/** Port of the development tooling that fills the Catalogue with test data. Never part of a business write. */
interface CatalogueTestDataSeeding
{
    /** Removes every Catalogue row (and nothing of other modules). */
    public function wipe(): void;

    /**
     * Loads the test data.
     *
     * @return array<string, int> rows created, by kind: dictionary_values, vehicles, glass_models,
     *                            eurocodes, oem_codes, fitments, thumbnail_mappings
     */
    public function seed(CatalogueSeedSize $size): array;
}
