<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Domain\Contracts\CatalogueTestDataSeeding;
use App\Modules\Catalogue\Domain\Enums\CatalogueSeedSize;

/**
 * Development tooling, not a business write: wipes the Catalogue and loads fresh test data. The
 * `seed-katalog` console command goes through it, because the UserInterface may not reach the
 * Infrastructure factories the seeding is built on.
 */
final class CatalogueTestData
{
    public function __construct(private readonly CatalogueTestDataSeeding $seeding) {}

    /**
     * @return array<string, int> rows created, by kind
     */
    public function reload(CatalogueSeedSize $size): array
    {
        $this->seeding->wipe();

        return $this->seeding->seed($size);
    }
}
