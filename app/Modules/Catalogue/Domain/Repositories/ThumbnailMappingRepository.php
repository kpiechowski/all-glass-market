<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Repositories;

use App\Core\Contracts\Repository;
use App\Modules\Catalogue\Domain\Entities\ThumbnailMapping;

/** @extends Repository<ThumbnailMapping> */
interface ThumbnailMappingRepository extends Repository
{
    public function findForValue(int $dictionaryValueId): ?ThumbnailMapping;

    /** @return list<ThumbnailMapping> */
    public function all(): array;
}
