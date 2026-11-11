<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Domain\Contracts\DictionaryEntry;
use App\Modules\Catalogue\Domain\Contracts\DictionaryReader;
use App\Modules\Catalogue\Domain\Enums\Dictionary;

/** FR-23, D-50: producers, positions and colours for picking and filtering; withdrawn values are not listed, but `namesOf` still reads them. */
final class CatalogueDictionaries
{
    public function __construct(private readonly DictionaryReader $reader) {}

    /** @return list<DictionaryEntry> */
    public function producers(): array
    {
        return $this->reader->active(Dictionary::Producer);
    }

    /** @return list<DictionaryEntry> */
    public function positions(): array
    {
        return $this->reader->active(Dictionary::Position);
    }

    /** @return list<DictionaryEntry> */
    public function colours(): array
    {
        return $this->reader->active(Dictionary::Colour);
    }

    /**
     * @param  list<int>  $valueIds
     * @return array<int, string> names by id, withdrawn values included
     */
    public function namesOf(array $valueIds): array
    {
        return $this->reader->names($valueIds);
    }
}
