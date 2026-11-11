<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\ValueObjects;

use App\Modules\Catalogue\Domain\Entities\ThumbnailMapping;

/** The catalogue settings as the drawing rules read them: mappings by dictionary value id. */
final readonly class ThumbnailSettings
{
    /** @param  array<int, ThumbnailMapping>  $mappings  by dictionary value id */
    private function __construct(private array $mappings) {}

    /** @param  list<ThumbnailMapping>  $mappings */
    public static function of(array $mappings): self
    {
        $byValue = [];

        foreach ($mappings as $mapping) {
            $byValue[$mapping->dictionaryValueId()] = $mapping;
        }

        return new self($byValue);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function for(int $dictionaryValueId): ?ThumbnailMapping
    {
        return $this->mappings[$dictionaryValueId] ?? null;
    }
}
