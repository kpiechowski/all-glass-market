<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Contracts;

use App\Modules\Catalogue\Domain\Enums\Dictionary;

/** Port behind the dictionary lists (FR-23). */
interface DictionaryReader
{
    /** @return list<DictionaryEntry> the dictionary's active values by name */
    public function active(Dictionary $dictionary): array;

    /**
     * @param  list<int>  $ids
     * @return array<int, string> names by id, withdrawn values included; unknown ids are left out
     */
    public function names(array $ids): array;
}
